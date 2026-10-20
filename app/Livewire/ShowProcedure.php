<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\ProcedureType;
use App\Models\Evidence;
use App\Models\Device;
use App\Models\PatrolAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ShowProcedure extends Component
{
    use WithFileUploads;

    public Procedure $procedure; // El caso actual
    
    // Variables para el formulario de la nueva bitácora
    public $action_type = 'Novedad';
    public $description = '';
    public $status = '';
    public $photo;
    // Nueva variable para el despacho tardío
    public $new_device_id = '';
    public $parent_id_to_link = '';

    // Categorización Operativa y Metadatos Dinámicos al operar el caso
    public $operational_procedure_type_id = '';
    public $currentMetaFields = [];
    public $metaValues = [];
    public $customMetaLabel = '';
    public $customMetaValue = '';

    // Se ejecuta al cargar la página y recibe el ID de la URL
    public function mount(Procedure $procedure)
    {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectors = $user->sectors->pluck('id')->toArray();
            if (!in_array($procedure->sector_id, $allowedSectors)) {
                abort(403, 'No tienes permisos para visualizar procedimientos de este sector.');
            }
        }

        // Cargamos las relaciones necesarias, incluyendo la patrulla y tipificación inicial
        $this->procedure = $procedure->load([
            'sector',
            'procedureType',
            'initialProcedureType',
            'device',
            'origin',
            'logs.user',
            'evidences.user',
            'patrolAssignment.crew',
            'children.logs',
            'parent',
        ]);
        $this->status = $procedure->status;
        $this->operational_procedure_type_id = $procedure->procedure_type_id;
        $this->syncMetaFieldsFromType($procedure->procedureType, $procedure->metadata ?? []);
    }

    public function syncMetaFieldsFromType(?ProcedureType $type, array $storedMeta = [])
    {
        $schema = $type ? $type->effective_meta_fields : [];
        $schemaKeys = collect($schema)->pluck('key')->toArray();

        // Incluir cualquier metadato adicional guardado previamente aunque no esté en el esquema base
        foreach ($storedMeta as $k => $v) {
            if (!in_array($k, $schemaKeys) && $v !== '' && !is_null($v)) {
                $schema[] = [
                    'key' => $k,
                    'label' => ucwords(str_replace('_', ' ', $k)),
                    'type' => 'text',
                    'placeholder' => '',
                    'options' => '',
                ];
            }
        }

        $this->currentMetaFields = $schema;
        $values = [];
        foreach ($this->currentMetaFields as $field) {
            $k = $field['key'];
            $values[$k] = $storedMeta[$k] ?? ($this->metaValues[$k] ?? '');
        }
        $this->metaValues = $values;
    }

    public function updatedOperationalProcedureTypeId($value)
    {
        if ($value) {
            $newType = ProcedureType::find($value);
            $this->syncMetaFieldsFromType($newType, $this->metaValues);
        }
    }

    public function addCustomMetadataField()
    {
        $label = trim($this->customMetaLabel);
        $val = trim($this->customMetaValue);
        if ($label === '') {
            return;
        }

        $key = Str::slug($label, '_');
        if ($key === '') {
            $key = 'dato_' . (count($this->currentMetaFields) + 1);
        }

        $exists = collect($this->currentMetaFields)->contains('key', $key);
        if (!$exists) {
            $this->currentMetaFields[] = [
                'key' => $key,
                'label' => $label,
                'type' => 'text',
                'placeholder' => '',
                'options' => '',
            ];
        }

        $this->metaValues[$key] = $val;
        $this->reset(['customMetaLabel', 'customMetaValue']);
    }

    public function saveMetadataAndCategory()
    {
        $oldTypeId = (int) $this->procedure->procedure_type_id;
        $newTypeId = (int) ($this->operational_procedure_type_id ?: $oldTypeId);

        $cleanMeta = collect($this->metaValues)
            ->filter(fn($val) => !is_null($val) && trim((string) $val) !== '')
            ->toArray();

        $updates = [
            'procedure_type_id' => $newTypeId,
            'metadata' => !empty($cleanMeta) ? $cleanMeta : null,
        ];

        if (!$this->procedure->initial_procedure_type_id) {
            $updates['initial_procedure_type_id'] = $oldTypeId;
        }

        $this->procedure->update($updates);
        $this->procedure->refresh();

        $logParts = [];
        if ($oldTypeId !== $newTypeId) {
            $oldName = ProcedureType::find($oldTypeId)?->name ?? 'Anterior';
            $newName = $this->procedure->procedureType?->name ?? 'Nuevo';
            $logParts[] = "Recategorización operativa del caso: cambia de \"{$oldName}\" a \"{$newName}\".";
        }

        if (!empty($cleanMeta)) {
            $summary = collect($this->procedure->formatted_metadata)
                ->map(fn($m) => "{$m['label']}: {$m['value']}")
                ->join(' | ');
            $logParts[] = "Antecedentes/Metadatos actualizados: [{$summary}].";
        }

        if (!empty($logParts)) {
            ProcedureLog::create([
                'procedure_id' => $this->procedure->id,
                'user_id' => Auth::id(),
                'action_type' => $oldTypeId !== $newTypeId ? 'Recategorización / Metadatos' : 'Actualización de Antecedentes',
                'description' => implode(' ', $logParts),
            ]);

            \App\Models\AuditLog::record(
                $oldTypeId !== $newTypeId ? 'procedure.recategorized' : 'procedure.metadata_updated',
                'Procedimientos',
                "Procedimiento #{$this->procedure->id}: " . implode(' ', $logParts),
                $this->procedure,
                ['old_type_id' => $oldTypeId, 'new_type_id' => $newTypeId, 'metadata' => $cleanMeta]
            );
        }

        $this->procedure->refresh();
        $this->syncMetaFieldsFromType($this->procedure->procedureType, $this->procedure->metadata ?? []);
        session()->flash('message', 'Categorización operativa y antecedentes (metadatos) guardados correctamente.');
    }

    // FUNCIÓN PARA DESPACHAR MÓVIL DESPUÉS DE CREADO EL CASO
    public function assignDevice()
    {
        $this->validate([
            'new_device_id' => 'required|exists:devices,id'
        ]);

        // 1. Buscamos si hay un turno activo para este móvil
        $activeAssignment = PatrolAssignment::where('device_id', $this->new_device_id)
            ->where('is_active', true)
            ->latest()
            ->first();

        // 2. Actualizamos el procedimiento y registramos hora de despacho para SLA
        $this->procedure->update([
            'device_id' => $this->new_device_id,
            'patrol_assignment_id' => $activeAssignment ? $activeAssignment->id : null,
            'status' => 'En Camino',
            'dispatched_at' => $this->procedure->dispatched_at ?? now(),
        ]);

        // 3. Dejamos constancia en la bitácora
        $deviceName = Device::find($this->new_device_id)->name;
        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Despacho',
            'description' => "Se asigna y despacha el móvil $deviceName al lugar del procedimiento.",
        ]);

        \App\Models\AuditLog::record(
            'procedure.dispatched',
            'Procedimientos',
            "Despacho de móvil {$deviceName} al Procedimiento #{$this->procedure->id}.",
            $this->procedure,
            ['device_id' => $this->new_device_id, 'device_name' => $deviceName]
        );

        // Refrescamos la página y limpiamos
        $this->reset('new_device_id');
        $this->status = 'En Camino';
        $this->procedure->refresh();
        
        session()->flash('message', 'Móvil despachado exitosamente.');
    }

    /**
     * 1. Confirmación de llegada al lugar por parte del Móvil (usando Georreferencia GPS)
     */
    public function confirmMobileArrival($lat = null, $lng = null)
    {
        $distance = Procedure::calculateDistanceMeters(
            $this->procedure->latitude,
            $this->procedure->longitude,
            $lat,
            $lng
        );

        $this->procedure->update([
            'mobile_arrived_at' => now(),
            'mobile_arrival_lat' => $lat ?: null,
            'mobile_arrival_lng' => $lng ?: null,
            'mobile_arrival_distance' => $distance,
        ]);

        if ($this->procedure->device_id && $lat && $lng) {
            Device::where('id', $this->procedure->device_id)->update([
                'last_lat' => $lat,
                'last_lng' => $lng,
                'last_location_at' => now(),
            ]);
        }

        $distanceText = !is_null($distance)
            ? " (Distancia georreferenciada al punto: {$distance} m)."
            : ($lat && $lng ? " (GPS: {$lat}, {$lng})." : " (Sin señal GPS disponible).");

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Llegada Móvil (GPS)',
            'description' => "El móvil reporta llegada al lugar desde terreno{$distanceText} Pendiente confirmación de operadora en Central.",
            'latitude' => $lat ?: null,
            'longitude' => $lng ?: null,
        ]);

        $this->procedure->refresh();
        session()->flash('message', 'Llegada en terreno (GPS) registrada. Pendiente validación de la operadora.');
    }

    /**
     * 2. Confirmación oficial de llegada por parte de la Operadora / Central
     */
    public function confirmOperatorArrival()
    {
        $now = now();
        $start = $this->procedure->dispatched_at ?? $this->procedure->created_at;
        $arrivalMins = (int) round($start->diffInMinutes($now));

        $this->procedure->update([
            'status' => 'En el Lugar',
            'dispatched_at' => $this->procedure->dispatched_at ?? $this->procedure->created_at,
            'arrived_at' => $this->procedure->arrived_at ?? $now,
        ]);

        $gpsNote = $this->procedure->mobile_arrived_at
            ? " (Validando reporte GPS previo del móvil" . (!is_null($this->procedure->mobile_arrival_distance) ? " a {$this->procedure->mobile_arrival_distance} m" : "") . ")."
            : " (Confirmación radial directa por Central).";

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Confirmación Llegada (Central)',
            'description' => "Operadora confirma móvil En el Lugar. Tiempo de respuesta/arribo: {$arrivalMins} min{$gpsNote}",
        ]);

        \App\Models\AuditLog::record(
            'procedure.arrival_confirmed',
            'Procedimientos',
            "Confirmación de arribo en Central para Procedimiento #{$this->procedure->id} (Tiempo respuesta: {$arrivalMins} min).",
            $this->procedure,
            ['arrival_minutes' => $arrivalMins, 'mobile_arrival_distance' => $this->procedure->mobile_arrival_distance]
        );

        $this->status = 'En el Lugar';
        $this->procedure->refresh();
        session()->flash('message', 'Llegada confirmada por Central. Procedimiento actualizado a "En el Lugar".');
    }

    public function saveLog()
    {
        // 1. Validar que no mande una nota vacía
        $this->validate([
            'action_type' => 'required',
            'description' => 'required_without:photo', // El texto es obligatorio solo si NO hay foto
            'photo' => 'nullable|image|max:5120', // Máximo 5MB por foto
        ]);
        
        if ($this->photo) {
            // Guarda en storage/app/public/evidences y devuelve la ruta
            $path = $this->photo->store('evidences', 'public');
            
            Evidence::create([
                'procedure_id' => $this->procedure->id,
                'user_id' => Auth::id(),
                'file_path' => $path,
            ]);
            
            // Si mandó foto pero no escribió descripción, le ponemos una automática
            if (empty($this->description)) {
                $this->description = 'Adjunta evidencia fotográfica.';
            }
        }
        
        // 2. Guardar la nueva anotación en la bitácora
        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => $this->action_type,
            'description' => $this->description,
        ]);

        // 3. Sincronizar estado y marcas de tiempo SLA según la acción o el cambio de estado
        $oldStatus = $this->procedure->status;
        $updates = [];
        if ($this->action_type === 'Llegada al lugar' && $this->status !== 'Cerrado') {
            $this->status = 'En el Lugar';
        } elseif ($this->action_type === 'Cierre de Procedimiento') {
            $this->status = 'Cerrado';
        }

        if ($this->status !== $this->procedure->status) {
            $updates['status'] = $this->status;
        }

        if ($this->status === 'En Camino' && !$this->procedure->dispatched_at) {
            $updates['dispatched_at'] = now();
        }

        if ($this->status === 'En el Lugar' && !$this->procedure->arrived_at) {
            $updates['arrived_at'] = now();
            if (!$this->procedure->dispatched_at) {
                $updates['dispatched_at'] = $this->procedure->created_at;
            }
        }

        if ($this->status === 'Cerrado' && !$this->procedure->closed_at) {
            $updates['closed_at'] = now();
        }

        if (!empty($updates)) {
            $this->procedure->update($updates);
            if ($oldStatus !== $this->status) {
                \App\Models\AuditLog::record(
                    'procedure.status_changed',
                    'Procedimientos',
                    "Cambio de estado en Procedimiento #{$this->procedure->id}: de \"{$oldStatus}\" a \"{$this->status}\".",
                    $this->procedure,
                    ['old_status' => $oldStatus, 'new_status' => $this->status, 'action_type' => $this->action_type]
                );
            }
        }

        // 4. Limpiar el formulario y recargar los datos
        $this->reset('description', 'photo');
        $this->action_type = 'Novedad';
        $this->procedure->refresh(); // Recarga el historial de la BD
    }

    public function linkToParent()
    {
        $this->validate([
            'parent_id_to_link' => 'required|exists:procedures,id|not_in:' . $this->procedure->id,
        ]);

        $parentCase = \App\Models\Procedure::find($this->parent_id_to_link);
        if ($parentCase->parent_id) {
            $this->addError('parent_id_to_link', 'El caso destino ya es un anexo. Por favor, vincula directamente al Caso Principal #' . $parentCase->parent_id);
            return;
        }

        // 2. Actualizamos ESTE caso (el hijo) a CERRADO
        $this->procedure->update([
            'parent_id' => $this->parent_id_to_link,
            'status' => 'Cerrado',
            'closed_at' => now(),
        ]);

        // 3. Bitácora en el Hijo
        \App\Models\ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action_type' => 'Cierre por Vinculación',
            'description' => 'Caso cerrado operativamente. Se anexó al Procedimiento Principal #' . $this->parent_id_to_link,
        ]);

        // 4. Bitácora en el Padre
        \App\Models\ProcedureLog::create([
            'procedure_id' => $this->parent_id_to_link,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action_type' => 'Anexo Recibido',
            'description' => 'Se vinculó el Procedimiento Anexo #' . $this->procedure->id . ' a este caso principal.',
        ]);

        $this->status = 'Cerrado';
        $this->reset('parent_id_to_link');
        $this->procedure->refresh();
        
        session()->flash('message', 'Procedimiento vinculado y cerrado exitosamente.');
    }

    public function render()
    {
        // Historial previo de otros procedimientos del mismo teléfono
        $previousCallerCases = collect();
        if (!empty($this->procedure->caller_phone)) {
            $previousCallerCases = Procedure::with('procedureType')
                ->where('caller_phone', $this->procedure->caller_phone)
                ->where('id', '!=', $this->procedure->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        $logActions = \App\Models\SystemCatalogItem::namesFor('procedure_log_actions', [
            'Novedad',
            'Llegada al lugar',
            'Contacto con Recurrente',
            'Coordinación Externa (Carabineros / SAMU / Bomberos)',
            'Cierre de Procedimiento',
        ]);

        return view('livewire.show-procedure', [
            'devices' => Device::withCount('activeProcedures')->orderBy('name')->get(),
            'procedureTypes' => ProcedureType::orderBy('name')->get(),
            'previousCallerCases' => $previousCallerCases,
            'logActions' => $logActions,
        ])->layout('layouts.app');
    }
}
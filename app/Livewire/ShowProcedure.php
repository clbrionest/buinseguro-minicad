<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Device;
use App\Models\Evidence;
use App\Models\PatrolAssignment;
use App\Models\PreventivePatrol;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\ProcedureType;
use App\Models\SystemCatalogItem;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ShowProcedure extends Component
{
    use WithFileUploads;

    public Procedure $procedure; // El caso actual

    // Variables para el formulario de la nueva bitácora
    public $action_type = 'Novedad';

    public $description = '';

    public $status = '';

    public $photo;

    /** @var array<int, mixed> */
    public $photos = [];

    // Campos contextuales según etapa/actuación (Derivación externa y Cierre)
    public $external_agency = '';

    public $custom_external_agency = '';

    public $external_reference = '';

    public $close_after_derivation = false;

    public $closure_resolution = '';

    public $manual_status_override = false;

    // Nueva variable para el despacho tardío
    public $new_device_id = '';

    public $parent_id_to_link = '';

    // Modal buscador de Caso Principal para agrupar duplicados
    public $isParentLinkModalOpen = false;

    public $parentLinkSearch = '';

    public $showClosedParentsInLink = false;

    // Modal de Reapertura de Caso Cerrado (solo Administrador / Supervisor)
    public bool $isReopenModalOpen = false;

    public string $reopen_status = 'En el Lugar';

    public string $reopen_reason = '';

    // Categorización Operativa y Metadatos Dinámicos al operar el caso
    public $operational_procedure_type_id = '';

    public $currentMetaFields = [];

    public $metaValues = [];

    public $customMetaLabel = '';

    public $customMetaValue = '';

    // Edición de anotaciones en Bitácora (solo el autor puede editar las propias)
    public $editing_log_id = null;

    public $editing_log_description = '';

    // Traspaso de procedimiento a otro inspector (Supervisor, Operador, Administrador)
    public $transfer_inspector_id = '';

    public $transfer_notes = '';

    // Se ejecuta al cargar la página y recibe el ID de la URL
    public function mount(Procedure $procedure)
    {
        $user = auth()->user();
        if ($user && ! $user->canAccessProcedure($procedure)) {
            AuditLog::record(
                'security.denied',
                'Procedimientos',
                "Intento no autorizado de visualizar u operar Procedimiento #{$procedure->id} por {$user->name}."
            );
            abort(403, 'No tienes permisos para visualizar ni operar este procedimiento.');
        }

        // Cargamos las relaciones necesarias, incluyendo la patrulla, llamadas y tipificación inicial
        $this->procedure = $procedure->load([
            'sector',
            'procedureType',
            'initialProcedureType',
            'device',
            'origin',
            'call',
            'calls',
            'logs.user',
            'evidences.user',
            'patrolAssignment.crew',
            'children.logs',
            'children.call',
            'children.calls',
            'children.evidences',
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
            if (str_starts_with((string) $k, '_') || is_array($v)) {
                continue;
            }
            if (! in_array($k, $schemaKeys) && $v !== '' && ! is_null($v)) {
                $schema[] = [
                    'key' => $k,
                    'label' => ucwords(str_replace('_', ' ', $k)),
                    'type' => 'text',
                    'placeholder' => '',
                    'options' => '',
                ];
            }
        }

        $user = auth()->user();
        $this->currentMetaFields = collect($schema)->map(function ($field) use ($user) {
            $k = $field['key'];
            $field['editable'] = $this->procedure->canUserEditMetaKey($k, $user);

            return $field;
        })->values()->toArray();

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
        if ($key === '' || str_starts_with($key, '_')) {
            $key = 'dato_'.(count($this->currentMetaFields) + 1);
        }

        $exists = collect($this->currentMetaFields)->contains('key', $key);
        if (! $exists) {
            $this->currentMetaFields[] = [
                'key' => $key,
                'label' => $label,
                'type' => 'text',
                'placeholder' => '',
                'options' => '',
                'editable' => true,
            ];
        }

        if ($this->procedure->canUserEditMetaKey($key, auth()->user())) {
            $this->metaValues[$key] = $val;
        }
        $this->reset(['customMetaLabel', 'customMetaValue']);
    }

    public function saveMetadataAndCategory()
    {
        $user = auth()->user();
        abort_unless($user && $user->canAccessProcedure($this->procedure), 403);

        $oldTypeId = (int) $this->procedure->procedure_type_id;
        $newTypeId = (int) ($this->operational_procedure_type_id ?: $oldTypeId);

        $existingMeta = is_array($this->procedure->metadata) ? $this->procedure->metadata : [];
        $authors = isset($existingMeta['_meta_authors']) && is_array($existingMeta['_meta_authors'])
            ? $existingMeta['_meta_authors']
            : [];

        $cleanMeta = [];

        // Conservar primero cualquier antecedente existente creado por otro usuario si quien edita es Inspector
        foreach ($existingMeta as $k => $existingVal) {
            if (str_starts_with((string) $k, '_') || is_array($existingVal)) {
                continue;
            }
            if (! isset($authors[$k]) && $existingVal !== '' && ! is_null($existingVal)) {
                $authors[$k] = $this->procedure->user_id;
            }
            if ($user->isInspectorOnly() && ! $this->procedure->canUserEditMetaKey((string) $k, $user)) {
                $cleanMeta[$k] = $existingVal;
            }
        }

        // Procesar los valores enviados en el formulario
        foreach ($this->metaValues as $k => $val) {
            if (str_starts_with((string) $k, '_') || is_array($val)) {
                continue;
            }

            if ($user->isInspectorOnly() && ! $this->procedure->canUserEditMetaKey((string) $k, $user)) {
                continue;
            }

            $trimmedVal = ! is_null($val) ? trim((string) $val) : '';
            if ($trimmedVal !== '') {
                $cleanMeta[$k] = $trimmedVal;
                if (! isset($authors[$k])) {
                    $authors[$k] = $user->id;
                }
            } else {
                unset($authors[$k]);
            }
        }

        if (! empty($authors)) {
            $cleanMeta['_meta_authors'] = $authors;
        }

        // Conservar metadatos internos de asignación, derivación y cierre si existen
        foreach (['_assigned_inspector_id', '_assigned_inspector_name', '_derived_to', '_external_ref', '_closure_resolution'] as $internalKey) {
            if (isset($existingMeta[$internalKey])) {
                $cleanMeta[$internalKey] = $existingMeta[$internalKey];
            }
        }

        $updates = [
            'procedure_type_id' => $newTypeId,
            'metadata' => ! empty($cleanMeta) ? $cleanMeta : null,
        ];

        if (! $this->procedure->initial_procedure_type_id) {
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

        if (! empty($cleanMeta)) {
            $summary = collect($this->procedure->formatted_metadata)
                ->map(fn ($m) => "{$m['label']}: {$m['value']}")
                ->join(' | ');
            $logParts[] = "Antecedentes/Metadatos actualizados: [{$summary}].";
        }

        if (! empty($logParts)) {
            ProcedureLog::create([
                'procedure_id' => $this->procedure->id,
                'user_id' => Auth::id(),
                'action_type' => $oldTypeId !== $newTypeId ? 'Recategorización / Metadatos' : 'Actualización de Antecedentes',
                'description' => implode(' ', $logParts),
            ]);

            AuditLog::record(
                $oldTypeId !== $newTypeId ? 'procedure.recategorized' : 'procedure.metadata_updated',
                'Procedimientos',
                "Procedimiento #{$this->procedure->id}: ".implode(' ', $logParts),
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
            'new_device_id' => 'required|exists:devices,id',
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

        AuditLog::record(
            'procedure.dispatched',
            'Procedimientos',
            "Despacho de móvil {$deviceName} al Procedimiento #{$this->procedure->id}.",
            $this->procedure,
            ['device_id' => $this->new_device_id, 'device_name' => $deviceName]
        );

        // Refrescamos la página y limpiamos
        $dispatchedDeviceId = (int) $this->new_device_id;
        $this->reset('new_device_id');
        $this->status = 'En Camino';
        $this->procedure->refresh();

        $this->dispatch('procedure-dispatched', procedureId: $this->procedure->id, deviceId: $dispatchedDeviceId);

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

        $distanceText = ! is_null($distance)
            ? " (Distancia georreferenciada al punto: {$distance} m)."
            : ($lat && $lng ? " (GPS: {$lat}, {$lng})." : ' (Sin señal GPS disponible).');

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
            ? ' (Validando reporte GPS previo del móvil'.(! is_null($this->procedure->mobile_arrival_distance) ? " a {$this->procedure->mobile_arrival_distance} m" : '').').'
            : ' (Confirmación radial directa por Central).';

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Confirmación Llegada (Central)',
            'description' => "Operadora confirma móvil En el Lugar. Tiempo de respuesta/arribo: {$arrivalMins} min{$gpsNote}",
        ]);

        AuditLog::record(
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

    public function isDerivationAction(?string $action = null): bool
    {
        $val = mb_strtolower(trim((string) ($action ?? $this->action_type)));

        return str_contains($val, 'coordinaci') || str_contains($val, 'derivaci');
    }

    public function isClosureAction(?string $action = null): bool
    {
        $val = mb_strtolower(trim((string) ($action ?? $this->action_type)));

        return str_contains($val, 'cierre');
    }

    public function isArrivalAction(?string $action = null): bool
    {
        $val = mb_strtolower(trim((string) ($action ?? $this->action_type)));

        return str_contains($val, 'llegada');
    }

    public function canManualOverrideStatus(): bool
    {
        $user = Auth::user();

        return (bool) ($user && $user->hasAnyRole(['Administrador', 'Supervisor']));
    }

    public function resolveNextStatus(): string
    {
        if ($this->manual_status_override && $this->canManualOverrideStatus() && trim((string) $this->status) !== '') {
            return (string) $this->status;
        }

        if ($this->isClosureAction()) {
            return 'Cerrado';
        }

        if ($this->isDerivationAction()) {
            return $this->close_after_derivation ? 'Cerrado' : 'Derivado';
        }

        if ($this->isArrivalAction()) {
            return 'En el Lugar';
        }

        return (string) $this->procedure->status;
    }

    public function updatedActionType(): void
    {
        $this->resetValidation(['external_agency', 'custom_external_agency', 'external_reference', 'closure_resolution', 'description']);

        if (! $this->isDerivationAction()) {
            $this->reset(['external_agency', 'custom_external_agency', 'external_reference', 'close_after_derivation']);
        }

        if (! $this->isClosureAction()) {
            $this->reset(['closure_resolution']);
        }

        if (! $this->manual_status_override) {
            $this->status = $this->resolveNextStatus();
        }
    }

    public function updatedCloseAfterDerivation(): void
    {
        if (! $this->manual_status_override) {
            $this->status = $this->resolveNextStatus();
        }
    }

    public function updatedManualStatusOverride(): void
    {
        if (! $this->manual_status_override || ! $this->canManualOverrideStatus()) {
            $this->manual_status_override = false;
            $this->status = $this->resolveNextStatus();
        }
    }

    public function removePendingPhoto(int $index): void
    {
        if (is_array($this->photos) && array_key_exists($index, $this->photos)) {
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function saveLog()
    {
        $user = Auth::user();
        abort_unless($user && $user->canAccessProcedure($this->procedure), 403);

        $isDerivation = $this->isDerivationAction();
        $isClosure = $this->isClosureAction();

        // 1. Validación contextual según el tipo de actuación elegida
        $rules = [
            'action_type' => 'required|string|max:255',
            'photo' => 'nullable|image|max:5120',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|max:5120',
        ];
        $messages = [
            'photos.max' => 'Puedes adjuntar un máximo de 10 imágenes por actuación.',
            'photos.*.image' => 'Todos los archivos adjuntos deben ser imágenes válidas.',
            'photos.*.max' => 'Cada imagen no puede superar los 5 MB.',
        ];

        if ($isDerivation) {
            $rules['external_agency'] = 'required|string|max:255';
            $messages['external_agency.required'] = 'Seleccione el organismo externo al que se deriva el procedimiento.';
            if ($this->external_agency === 'OTRO') {
                $rules['custom_external_agency'] = 'required|string|max:150';
                $messages['custom_external_agency.required'] = 'Especifique el nombre del organismo externo.';
            }
            $rules['external_reference'] = 'nullable|string|max:150';
            $rules['description'] = 'nullable|string|max:2000';
        } elseif ($isClosure) {
            $rules['closure_resolution'] = 'required|string|max:255';
            $messages['closure_resolution.required'] = 'Seleccione la resolución final para cerrar el procedimiento.';
            $rules['description'] = 'nullable|string|max:2000';
        } else {
            $hasAnyPhoto = ! empty($this->photo) || (is_array($this->photos) && count($this->photos) > 0);
            $rules['description'] = ($hasAnyPhoto ? 'nullable' : 'required').'|string|max:2000';
            $messages['description.required'] = 'Ingrese el detalle de la actuación o adjunte al menos una evidencia fotográfica.';
        }

        $this->validate($rules, $messages);

        $resolvedAgency = null;
        $externalRef = trim((string) $this->external_reference);
        $resolvedClosure = null;

        if ($isDerivation) {
            $resolvedAgency = $this->external_agency === 'OTRO'
                ? trim((string) $this->custom_external_agency)
                : trim((string) $this->external_agency);
        }

        if ($isClosure) {
            $resolvedClosure = trim((string) $this->closure_resolution);
        }

        $uploadedCount = 0;
        $filesToStore = [];
        if ($this->photo) {
            $filesToStore[] = $this->photo;
        }
        if (is_array($this->photos)) {
            foreach ($this->photos as $pFile) {
                if ($pFile) {
                    $filesToStore[] = $pFile;
                }
            }
        }

        foreach ($filesToStore as $imgFile) {
            $path = $imgFile->store('evidences', 'public');

            Evidence::create([
                'procedure_id' => $this->procedure->id,
                'user_id' => Auth::id(),
                'file_path' => $path,
            ]);
            $uploadedCount++;
        }

        // 2. Construir descripción estructurada y legible para la bitácora
        $userDescription = trim((string) $this->description);
        if ($isDerivation) {
            $header = "Derivado a: [{$resolvedAgency}]";
            if ($externalRef !== '') {
                $header .= " (Ref: {$externalRef})";
            }
            if ($this->close_after_derivation) {
                $header .= ' — Cierre inmediato tras derivación';
            }
            $finalDescription = $userDescription !== ''
                ? "{$header}. {$userDescription}"
                : "{$header}.";
        } elseif ($isClosure) {
            $header = "Resolución de Cierre: [{$resolvedClosure}]";
            $finalDescription = $userDescription !== ''
                ? "{$header}. {$userDescription}"
                : "{$header}.";
        } else {
            $finalDescription = $userDescription !== ''
                ? $userDescription
                : ($uploadedCount > 1 ? "Adjunta {$uploadedCount} evidencias fotográficas." : 'Adjunta evidencia fotográfica.');
        }

        // 3. Guardar la nueva anotación en la bitácora
        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => $this->action_type,
            'description' => $finalDescription,
        ]);

        // 4. Sincronizar estado automático, metadatos estructurados y marcas de tiempo SLA
        $oldStatus = $this->procedure->status;
        $nextStatus = $this->resolveNextStatus();
        $this->status = $nextStatus;

        $updates = [];
        if ($nextStatus !== $oldStatus) {
            $updates['status'] = $nextStatus;
        }

        if ($isDerivation || $isClosure) {
            $meta = is_array($this->procedure->metadata) ? $this->procedure->metadata : [];
            if ($isDerivation && $resolvedAgency) {
                $meta['_derived_to'] = $resolvedAgency;
                if ($externalRef !== '') {
                    $meta['_external_ref'] = $externalRef;
                }
                if ($this->close_after_derivation) {
                    $meta['_closure_resolution'] = "Derivado a {$resolvedAgency}";
                }
            }
            if ($isClosure && $resolvedClosure) {
                $meta['_closure_resolution'] = $resolvedClosure;
            }
            $updates['metadata'] = $meta;
        }

        if ($nextStatus === 'En Camino' && ! $this->procedure->dispatched_at) {
            $updates['dispatched_at'] = now();
        }

        if ($nextStatus === 'En el Lugar' && ! $this->procedure->arrived_at) {
            $updates['arrived_at'] = now();
            if (! $this->procedure->dispatched_at) {
                $updates['dispatched_at'] = $this->procedure->created_at;
            }
        }

        if ($nextStatus === 'Cerrado' && ! $this->procedure->closed_at) {
            $updates['closed_at'] = now();
        }

        if (! empty($updates)) {
            $this->procedure->update($updates);
            if ($oldStatus !== $nextStatus) {
                AuditLog::record(
                    'procedure.status_changed',
                    'Procedimientos',
                    "Cambio de estado en Procedimiento #{$this->procedure->id}: de \"{$oldStatus}\" a \"{$nextStatus}\".",
                    $this->procedure,
                    array_filter([
                        'old_status' => $oldStatus,
                        'new_status' => $nextStatus,
                        'action_type' => $this->action_type,
                        'derived_to' => $resolvedAgency,
                        'external_reference' => $externalRef !== '' ? $externalRef : null,
                        'closure_resolution' => $resolvedClosure,
                    ], fn ($v) => ! is_null($v))
                );
            }
        }

        // 5. Limpiar el formulario y recargar los datos
        $this->reset([
            'description',
            'photo',
            'photos',
            'external_agency',
            'custom_external_agency',
            'external_reference',
            'close_after_derivation',
            'closure_resolution',
            'manual_status_override',
        ]);
        $this->action_type = 'Novedad';
        $this->procedure->refresh();
        $this->status = $this->procedure->status;
    }

    public function openParentLinkModal(): void
    {
        $this->resetValidation(['parent_id_to_link']);
        $this->parentLinkSearch = '';
        $this->showClosedParentsInLink = false;
        $this->isParentLinkModalOpen = true;
    }

    public function closeParentLinkModal(): void
    {
        $this->isParentLinkModalOpen = false;
        $this->reset(['parentLinkSearch', 'showClosedParentsInLink']);
    }

    public function linkToParent(?int $targetParentId = null): void
    {
        if (! is_null($targetParentId)) {
            $this->parent_id_to_link = $targetParentId;
        }

        $this->validate([
            'parent_id_to_link' => 'required|exists:procedures,id|not_in:'.$this->procedure->id,
        ], [
            'parent_id_to_link.required' => 'Seleccione el caso principal al que desea anexar este procedimiento.',
            'parent_id_to_link.not_in' => 'No puede vincular un procedimiento consigo mismo.',
        ]);

        // Evitar encadenamiento de árboles (un caso que ya tiene hijos no puede pasar a ser hijo de otro)
        if ($this->procedure->children()->exists()) {
            $this->addError('parent_id_to_link', 'Este procedimiento ya es un Caso Principal con anexos vinculados y no puede subordinarse a otro caso.');

            return;
        }

        $parentCase = Procedure::findOrFail($this->parent_id_to_link);
        if ($parentCase->parent_id) {
            $this->addError('parent_id_to_link', 'El caso destino ya es un anexo. Por favor, vincula directamente al Caso Principal #'.$parentCase->parent_id);

            return;
        }

        $hadDeviceName = $this->procedure->device?->name;

        // 2. Actualizamos ESTE caso (el hijo) a CERRADO
        $this->procedure->update([
            'parent_id' => $parentCase->id,
            'status' => 'Cerrado',
            'closed_at' => $this->procedure->closed_at ?? now(),
        ]);

        // 3. Bitácora en el Hijo
        $deviceNote = $hadDeviceName ? " (Móvil {$hadDeviceName} liberado de este duplicado)." : '.';
        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Cierre por Vinculación',
            'description' => "Caso cerrado operativamente. Se anexó al Procedimiento Principal #{$parentCase->id}{$deviceNote}",
        ]);

        // 4. Bitácora en el Padre
        ProcedureLog::create([
            'procedure_id' => $parentCase->id,
            'user_id' => Auth::id(),
            'action_type' => 'Anexo Recibido',
            'description' => "Se vinculó el Procedimiento Anexo #{$this->procedure->id} ({$this->procedure->exact_address}) a este caso principal.",
        ]);

        // 5. Registro oficial en AuditLog
        AuditLog::record(
            'procedure.linked_to_parent',
            'Procedimientos',
            "Procedimiento #{$this->procedure->id} anexado y cerrado bajo el Caso Principal #{$parentCase->id}.",
            $this->procedure,
            [
                'child_procedure_id' => $this->procedure->id,
                'parent_procedure_id' => $parentCase->id,
                'released_device' => $hadDeviceName,
            ]
        );

        $this->status = 'Cerrado';
        $this->reset('parent_id_to_link');
        $this->closeParentLinkModal();
        $this->procedure->refresh();
        $this->procedure->load(['parent', 'children.calls', 'children.evidences']);

        session()->flash('message', "Procedimiento vinculado al Caso #{$parentCase->id} y cerrado exitosamente.");
    }

    public function unlinkChildProcedure(int $childId): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            abort(403, 'Solo Administrador o Supervisor pueden desvincular un caso anexo.');
        }

        $child = Procedure::where('parent_id', $this->procedure->id)->findOrFail($childId);
        $reopenedStatus = $child->device_id ? 'En Camino' : 'Pendiente';

        $child->update([
            'parent_id' => null,
            'status' => $reopenedStatus,
            'closed_at' => null,
        ]);

        ProcedureLog::create([
            'procedure_id' => $child->id,
            'user_id' => $user->id,
            'action_type' => 'Desvinculación de Anexo',
            'description' => "Se desvinculó del Caso Principal #{$this->procedure->id}. Procedimiento reabierto en estado \"{$reopenedStatus}\".",
        ]);

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => $user->id,
            'action_type' => 'Anexo Desvinculado',
            'description' => "Se desvinculó el Procedimiento Anexo #{$child->id} de este caso principal.",
        ]);

        AuditLog::record(
            'procedure.unlinked_from_parent',
            'Procedimientos',
            "Procedimiento Anexo #{$child->id} desvinculado del Caso Principal #{$this->procedure->id} por {$user->name}.",
            $child,
            [
                'child_procedure_id' => $child->id,
                'parent_procedure_id' => $this->procedure->id,
                'reopened_status' => $reopenedStatus,
            ]
        );

        $this->procedure->refresh();
        $this->procedure->load(['children.logs', 'children.call', 'children.calls', 'children.evidences', 'call', 'calls']);
        session()->flash('message', "Caso Anexo #{$child->id} desvinculado y reabierto en estado {$reopenedStatus}.");
    }

    public function openReopenModal(): void
    {
        abort_unless($this->canManualOverrideStatus(), 403, 'Solo Administrador o Supervisor pueden reabrir un procedimiento cerrado.');

        $this->resetValidation(['reopen_status', 'reopen_reason']);
        $this->reopen_reason = '';
        $this->reopen_status = $this->procedure->arrived_at
            ? 'En el Lugar'
            : ($this->procedure->device_id ? 'En Camino' : 'Pendiente');
        $this->isReopenModalOpen = true;
    }

    public function closeReopenModal(): void
    {
        $this->isReopenModalOpen = false;
        $this->reset(['reopen_reason']);
        $this->resetValidation(['reopen_status', 'reopen_reason']);
    }

    public function reopenProcedure(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['Administrador', 'Supervisor'])) {
            abort(403, 'Solo Administrador o Supervisor pueden reabrir un procedimiento cerrado.');
        }

        if ($this->procedure->status !== 'Cerrado') {
            $this->closeReopenModal();

            return;
        }

        if ($this->procedure->parent_id) {
            $this->addError('reopen_reason', "Este caso está cerrado como anexo del Caso Principal #{$this->procedure->parent_id}. Desvincúlalo desde el caso principal para reabrirlo.");

            return;
        }

        $this->validate([
            'reopen_status' => 'required|in:Pendiente,En Camino,En el Lugar,Derivado',
            'reopen_reason' => 'required|string|min:5|max:500',
        ], [
            'reopen_reason.required' => 'Debe indicar el motivo de la reapertura para trazabilidad.',
            'reopen_reason.min' => 'El motivo de reapertura debe tener al menos 5 caracteres.',
        ]);

        $reason = trim($this->reopen_reason);
        $oldStatus = $this->procedure->status;

        $this->procedure->update([
            'status' => $this->reopen_status,
            'closed_at' => null,
        ]);

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => $user->id,
            'action_type' => 'Reapertura de Procedimiento',
            'description' => "Procedimiento reabierto por {$user->name} en estado \"{$this->reopen_status}\". Motivo: {$reason}",
        ]);

        AuditLog::record(
            'procedure.reopened',
            'Procedimientos',
            "Reapertura de Procedimiento #{$this->procedure->id} por {$user->name} (nuevo estado: {$this->reopen_status}). Motivo: {$reason}",
            $this->procedure,
            [
                'previous_status' => $oldStatus,
                'new_status' => $this->reopen_status,
                'reason' => $reason,
            ]
        );

        $this->status = $this->reopen_status;
        $this->closeReopenModal();
        $this->procedure->refresh();
        session()->flash('message', "Procedimiento #{$this->procedure->id} reabierto en estado \"{$this->status}\". Ya puedes corregir o agregar información.");
    }

    public function startEditingLog(int $logId): void
    {
        $log = ProcedureLog::where('procedure_id', $this->procedure->id)->findOrFail($logId);
        $user = Auth::user();

        if (! $user || ((int) $log->user_id !== (int) $user->id && ! $user->hasRole('Administrador'))) {
            abort(403, 'No tienes permisos para editar antecedentes o anotaciones registradas por otros usuarios.');
        }

        $this->editing_log_id = $log->id;
        $this->editing_log_description = $log->description;
    }

    public function cancelEditingLog(): void
    {
        $this->reset(['editing_log_id', 'editing_log_description']);
    }

    public function saveEditedLog(): void
    {
        if (! $this->editing_log_id) {
            return;
        }

        $log = ProcedureLog::where('procedure_id', $this->procedure->id)->findOrFail($this->editing_log_id);
        $user = Auth::user();

        if (! $user || ((int) $log->user_id !== (int) $user->id && ! $user->hasRole('Administrador'))) {
            abort(403, 'No tienes permisos para editar antecedentes o anotaciones registradas por otros usuarios.');
        }

        $this->validate([
            'editing_log_description' => 'required|string|max:2000',
        ]);

        $oldDescription = $log->description;
        $log->update([
            'description' => trim($this->editing_log_description),
        ]);

        AuditLog::record(
            'procedure_log.updated',
            'Procedimientos',
            "Edición de anotación #{$log->id} en Procedimiento #{$this->procedure->id}.",
            $this->procedure,
            ['log_id' => $log->id, 'old_description' => $oldDescription, 'new_description' => $log->description]
        );

        $this->cancelEditingLog();
        $this->procedure->refresh();
        session()->flash('message', 'Anotación actualizada correctamente.');
    }

    public function deleteLog(int $logId): void
    {
        $log = ProcedureLog::where('procedure_id', $this->procedure->id)->findOrFail($logId);
        $user = Auth::user();

        // Los inspectores no pueden eliminar anotaciones/antecedentes; solo Administrador puede eliminar
        if (! $user || ! $user->hasRole('Administrador')) {
            abort(403, 'No tienes permisos para eliminar antecedentes de la bitácora.');
        }

        $log->delete();
        $this->procedure->refresh();
        session()->flash('message', 'Anotación eliminada.');
    }

    public function transferProcedure(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['Administrador', 'Supervisor', 'Operador'])) {
            abort(403, 'Solo Administrador, Supervisor u Operador pueden traspasar un procedimiento a otro inspector.');
        }

        $this->validate([
            'transfer_inspector_id' => 'required|exists:users,id',
            'transfer_notes' => 'nullable|string|max:500',
        ], [
            'transfer_inspector_id.required' => 'Seleccione el inspector al que desea traspasar el procedimiento.',
        ]);

        $targetInspector = User::findOrFail($this->transfer_inspector_id);

        // Buscar si el inspector destino tiene un turno activo en flota o patrullaje preventivo en curso
        $activeAssignment = PatrolAssignment::with('device')
            ->where('is_active', true)
            ->whereHas('crew', fn ($q) => $q->where('users.id', $targetInspector->id))
            ->latest()
            ->first();

        $targetDeviceId = $activeAssignment?->device_id;
        if (! $targetDeviceId) {
            $activePatrol = PreventivePatrol::where('status', 'En Curso')
                ->where('user_id', $targetInspector->id)
                ->latest('started_at')
                ->first();
            $targetDeviceId = $activePatrol?->device_id;
        }

        $meta = is_array($this->procedure->metadata) ? $this->procedure->metadata : [];
        $meta['_assigned_inspector_id'] = (int) $targetInspector->id;
        $meta['_assigned_inspector_name'] = $targetInspector->name;

        $updates = [
            'metadata' => $meta,
            'dispatched_at' => now(),
        ];

        if ($targetDeviceId) {
            $updates['device_id'] = $targetDeviceId;
        }
        if ($activeAssignment) {
            $updates['patrol_assignment_id'] = $activeAssignment->id;
        }
        if ($this->procedure->status === 'Pendiente') {
            $updates['status'] = 'En Camino';
            $this->status = 'En Camino';
        }

        $this->procedure->update($updates);

        $unitText = $activeAssignment?->device ? " (Unidad: {$activeAssignment->device->name})" : '';
        $notesText = trim((string) $this->transfer_notes) !== '' ? ' Motivo/Instrucción: '.trim((string) $this->transfer_notes) : '';

        ProcedureLog::create([
            'procedure_id' => $this->procedure->id,
            'user_id' => $user->id,
            'action_type' => 'Traspaso de Procedimiento',
            'description' => "Se traspasa la continuidad del procedimiento al inspector {$targetInspector->name}{$unitText}.{$notesText}",
        ]);

        AuditLog::record(
            'procedure.transferred',
            'Procedimientos',
            "Procedimiento #{$this->procedure->id} traspasado al inspector {$targetInspector->name}{$unitText}.",
            $this->procedure,
            [
                'transferred_to_user_id' => $targetInspector->id,
                'transferred_to_name' => $targetInspector->name,
                'device_id' => $this->procedure->device_id,
                'notes' => $this->transfer_notes,
            ]
        );

        $this->reset(['transfer_inspector_id', 'transfer_notes']);
        $this->procedure->refresh();

        $this->dispatch('procedure-dispatched', procedureId: $this->procedure->id, deviceId: $this->procedure->device_id);
        session()->flash('message', "Procedimiento traspasado exitosamente a {$targetInspector->name}.");
    }

    public function applyResolution(string $resolution): void
    {
        $clean = trim($resolution);
        if ($clean === '') {
            return;
        }

        if (str_contains(strtoupper($clean), 'DERIVAD') || str_contains(strtoupper($clean), 'DERIVACION')) {
            $this->action_type = 'Coordinación Externa (Carabineros / SAMU / Bomberos)';
            $this->external_agency = $clean;
            $this->closure_resolution = '';
        } else {
            $this->action_type = 'Cierre de Procedimiento';
            $this->closure_resolution = $clean;
            $this->external_agency = '';
        }

        $this->status = $this->resolveNextStatus();
    }

    /**
     * Separa el catálogo de resoluciones en Organismos Externos (derivaciones) y Resoluciones de Cierre.
     *
     * @return array{external_agencies: array<int, string>, closure_resolutions: array<int, string>}
     */
    public static function splitResolutionsCatalog(): array
    {
        $allResolutions = SystemCatalogItem::namesFor('procedure_resolutions', []);

        $externalAgencies = [];
        $closureResolutions = [];

        foreach ($allResolutions as $item) {
            $upper = mb_strtoupper(trim($item));
            if (str_contains($upper, 'DERIVAD') || str_contains($upper, 'DERIVACION')) {
                $externalAgencies[] = $item;
            } else {
                $closureResolutions[] = $item;
            }
        }

        if (empty($externalAgencies)) {
            $externalAgencies = [
                'DERIVADO A CARABINEROS',
                'DERIVADO A BOMBEROS',
                'DERIVADO A AMBULANCIAS / SAMU / SAPU',
                'DERIVADO A PDI',
                'DERIVADO A EMPRESA EXTERNA (CGE / AGUAS ANDINAS)',
                'DERIVADO A DEPARTAMENTO MUNICIPAL',
            ];
        }

        if (empty($closureResolutions)) {
            $closureResolutions = [
                'SOLUCIONADO EN EL LUGAR',
                'SIN NOVEDAD',
                'FALSA ALARMA',
                'LABOR EDUCATIVA',
                'INFRACCION MUNICIPAL',
                'INFRACCION TRANSITO',
                'DENUNCIA',
                'DETENIDO BUIN SEGURO',
            ];
        }

        return [
            'external_agencies' => $externalAgencies,
            'closure_resolutions' => $closureResolutions,
        ];
    }

    public function render()
    {
        // Historial previo de otros procedimientos del mismo teléfono
        $previousCallerCases = collect();
        if (! empty($this->procedure->caller_phone)) {
            $previousCallerCases = Procedure::with('procedureType')
                ->where('caller_phone', $this->procedure->caller_phone)
                ->where('id', '!=', $this->procedure->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        $logActions = SystemCatalogItem::namesFor('procedure_log_actions', [
            'Novedad',
            'Llegada al lugar',
            'Contacto con Recurrente',
            'Coordinación Externa (Carabineros / SAMU / Bomberos)',
            'Cierre de Procedimiento',
        ]);

        // Coherencia de etapas: si el caso ya llegó al lugar o fue derivado, ocultar "Llegada al lugar"
        $alreadyArrived = in_array($this->procedure->status, ['En el Lugar', 'Derivado'], true)
            || ! is_null($this->procedure->arrived_at);

        if ($alreadyArrived) {
            $logActions = array_values(array_filter(
                $logActions,
                fn (string $act) => ! $this->isArrivalAction($act)
            ));
        }

        $catalogs = self::splitResolutionsCatalog();

        $inspectors = User::role('Inspector')
            ->orderBy('name')
            ->get()
            ->map(function (User $insp) {
                $activeShift = PatrolAssignment::with('device')
                    ->where('is_active', true)
                    ->whereHas('crew', fn ($q) => $q->where('users.id', $insp->id))
                    ->latest()
                    ->first();

                return [
                    'id' => $insp->id,
                    'name' => $insp->name,
                    'unit' => $activeShift?->device?->name,
                ];
            });

        // Candidatos para el Modal Buscador de Caso Principal (excluye el actual y casos hijos)
        $candidateParents = collect();
        if ($this->isParentLinkModalOpen) {
            $term = trim((string) $this->parentLinkSearch);
            $includeClosed = (bool) $this->showClosedParentsInLink || ($term !== '' && is_numeric($term));
            $currentSectorId = (int) $this->procedure->sector_id;

            $parentQuery = Procedure::with(['procedureType', 'sector'])
                ->where('id', '!=', $this->procedure->id)
                ->whereNull('parent_id')
                ->when(! $includeClosed, fn ($q) => $q->where('status', '!=', 'Cerrado'))
                ->orderByRaw('CASE WHEN sector_id = ? THEN 0 ELSE 1 END ASC', [$currentSectorId])
                ->orderByRaw("CASE WHEN status = 'Cerrado' THEN 1 ELSE 0 END ASC")
                ->orderBy('created_at', 'desc');

            if ($term !== '') {
                $parentQuery->where(function ($q) use ($term) {
                    if (is_numeric($term)) {
                        $q->where('id', (int) $term)
                            ->orWhere('caller_phone', 'like', "%{$term}%");
                    } else {
                        $q->where('exact_address', 'like', "%{$term}%")
                            ->orWhere('caller_name', 'like', "%{$term}%")
                            ->orWhere('caller_phone', 'like', "%{$term}%")
                            ->orWhereHas('procedureType', fn ($sub) => $sub->where('name', 'like', "%{$term}%"))
                            ->orWhereHas('sector', fn ($sub) => $sub->where('name', 'like', "%{$term}%"));
                    }
                });
            }

            $candidateParents = $parentQuery->take(20)->get();
        }

        $linkedCalls = $this->procedure->all_linked_calls;

        return view('livewire.show-procedure', [
            'devices' => Device::withCount('activeProcedures')->orderBy('name')->get(),
            'procedureTypes' => ProcedureType::orderBy('name')->get(),
            'previousCallerCases' => $previousCallerCases,
            'logActions' => $logActions,
            'externalAgencies' => $catalogs['external_agencies'],
            'closureResolutions' => $catalogs['closure_resolutions'],
            'resolvedNextStatus' => $this->resolveNextStatus(),
            'canManualOverrideStatus' => $this->canManualOverrideStatus(),
            'candidateParents' => $candidateParents,
            'linkedCalls' => $linkedCalls,
            'inspectors' => $inspectors,
        ])->layout('layouts.app');
    }
}

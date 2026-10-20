<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sector;
use App\Models\ProcedureType;
use App\Models\Device;
use App\Models\Origin;
use App\Models\Procedure;
use App\Models\ProcedureLog;
use App\Models\Landmark;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On; // <-- ¡NUEVA IMPORTACIÓN VITAL PARA ESCUCHAR EVENTOS!

class CreateProcedure extends Component
{
    public $isOpen = false; // Controla si el modal está visible

    // Variables del formulario
    public $origin_id = '';
    public $sector_id = '';
    public $procedure_type_id = '';
    public $device_id = '';
    
    // NUEVAS variables geográficas
    public $landmark_id = '';
    public $formatted_address = '';
    public $exact_address = ''; 
    public $address_reference = '';
    public $latitude = '';
    public $longitude = '';
    public $is_preventive = false; 

    public $caller_name = '';
    public $caller_id_document = '';
    public $caller_phone = ''; // <-- Aquí aterrizará el número de Asterisk
    public $initial_description = '';

    // Metadatos dinámicos según Tipo de Procedimiento
    public $currentMetaFields = [];
    public $metaValues = [];

    public $sectors, $procedureTypes, $devices, $origins, $landmarks; 
    public $call_id = null; // <-- Para almacenar el ID de la llamada entrante

    // Historial previo del número telefónico ingresado
    public $callerHistory = [];
    public $callerStats = [
        'total_cases' => 0,
        'total_calls' => 0,
        'pranks' => 0,
    ];


    public function mount()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $this->sectors = Sector::orderBy('name')->get();
        } else {
            $this->sectors = $user->sectors()->orderBy('name')->get();
        }

        $this->procedureTypes = ProcedureType::orderBy('name')->get();
        $this->devices = Device::withCount('activeProcedures')->orderBy('name')->get();
        $this->origins = Origin::orderBy('name')->get();
        $this->landmarks = Landmark::orderBy('name')->get();
    }

    public function updatedProcedureTypeId($value)
    {
        if ($value) {
            $type = ProcedureType::find($value);
            $this->currentMetaFields = $type ? $type->effective_meta_fields : [];
            // Conservar valores compatibles o inicializar claves nuevas
            $newValues = [];
            foreach ($this->currentMetaFields as $field) {
                $k = $field['key'];
                $newValues[$k] = $this->metaValues[$k] ?? '';
            }
            $this->metaValues = $newValues;
        } else {
            $this->currentMetaFields = [];
            $this->metaValues = [];
        }
    }

    public function updatedLandmarkId($value)
    {
        if ($value) {
            $landmark = Landmark::find($value);
            $this->exact_address = $landmark->name . ' (' . $landmark->address . ')';
            $this->formatted_address = $landmark->address;
            $this->latitude = $landmark->latitude;
            $this->longitude = $landmark->longitude;
            
            $this->dispatch('location-updated', lat: $this->latitude, lng: $this->longitude);
        } else {
            $this->reset(['exact_address', 'formatted_address', 'latitude', 'longitude']);
        }
    }

    public function updatedCallerPhone($value)
    {
        $this->loadCallerHistory($value);
    }

    public function loadCallerHistory($phone)
    {
        $phone = trim((string) $phone);

        if (strlen($phone) < 4) {
            $this->callerHistory = [];
            $this->callerStats = ['total_cases' => 0, 'total_calls' => 0, 'pranks' => 0];
            return;
        }

        $recentCases = Procedure::with(['procedureType', 'sector'])
            ->where('caller_phone', $phone)
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get();

        $this->callerStats = [
            'total_cases' => Procedure::where('caller_phone', $phone)->count(),
            'total_calls' => \App\Models\Call::where('caller_id', $phone)->count(),
            'pranks' => \App\Models\Call::where('caller_id', $phone)->where('classification', 'Pitanza / Broma')->count(),
        ];

        // Autocompletar nombre y RUT si ya existen en un caso previo y están en blanco
        $lastWithCaller = $recentCases->first(fn($c) => !empty($c->caller_name));
        if ($lastWithCaller) {
            if (empty($this->caller_name)) {
                $this->caller_name = $lastWithCaller->caller_name;
            }
            if (empty($this->caller_id_document) && !empty($lastWithCaller->caller_id_document)) {
                $this->caller_id_document = $lastWithCaller->caller_id_document;
            }
        }

        $this->callerHistory = $recentCases->map(fn($p) => [
            'id' => $p->id,
            'date' => $p->created_at->format('d/m/Y H:i'),
            'type' => $p->procedureType->name ?? 'N/A',
            'sector' => $p->sector->name ?? 'N/A',
            'address' => $p->exact_address,
            'status' => $p->status,
        ])->toArray();
    }

    // --- MAGIA APLICADA AQUÍ ---
    // Le decimos a Livewire que cuando alguien grite "openModal" (como el popup), ejecute esto.
    #[On('openModal')]
    public function openModal($caller_id = null, $call_id = null) // Recibimos el segundo parámetro
    { 
        $this->resetForm();
        $this->resetValidation();

        if ($caller_id) {
            $this->caller_phone = $caller_id;
            $this->loadCallerHistory($caller_id);
        }
        
        if ($call_id) {
            $this->call_id = $call_id; // <--- GUARDAMOS EL ID EN LA MEMORIA DEL COMPONENTE
        }

        $this->procedureTypes = ProcedureType::orderBy('name')->get();
        $this->devices = Device::withCount('activeProcedures')->orderBy('name')->get();
        $this->isOpen = true; 
    }

    public function closeModal() { 
        $this->isOpen = false; 
        $this->resetForm(); 
    }

    public function resetForm()
    {
        $this->reset([
            'origin_id',
            'sector_id',
            'procedure_type_id',
            'device_id',
            'landmark_id',
            'formatted_address',
            'exact_address',
            'address_reference',
            'latitude',
            'longitude',
            'is_preventive',
            'caller_name',
            'caller_id_document',
            'caller_phone',
            'initial_description',
            'currentMetaFields',
            'metaValues',
            'call_id',
            'callerHistory',
        ]);
        $this->callerStats = ['total_cases' => 0, 'total_calls' => 0, 'pranks' => 0];
    }

    public function save()
    {
        $this->validate([
            'origin_id' => 'required',
            'sector_id' => 'required',
            'procedure_type_id' => 'required',
            'exact_address' => 'required', 
        ]);

        $user = Auth::user();
        if ($user && !$user->hasAnyRole(['Administrador', 'Supervisor'])) {
            $allowedSectorIds = $user->sectors()->pluck('sectors.id')->map(fn($id) => (int) $id)->toArray();
            if (!in_array((int) $this->sector_id, $allowedSectorIds, true)) {
                \App\Models\AuditLog::record(
                    'security.denied',
                    'Procedimientos',
                    "Intento de crear procedimiento en sector no autorizado (ID: {$this->sector_id})."
                );
                abort(403, 'No tienes permisos para ingresar procedimientos en este sector.');
            }
        }

        $status = $this->device_id ? 'En Camino' : 'Pendiente';
        $dispatchedAt = $this->device_id ? now() : null;
        $activeAssignmentId = null;

        // BÚSQUEDA INTELIGENTE DE PATRULLA
        if ($this->device_id) {
            $activeAssignment = \App\Models\PatrolAssignment::where('device_id', $this->device_id)
                ->where('is_active', true)
                ->latest()
                ->first();
                
            if ($activeAssignment) {
                $activeAssignmentId = $activeAssignment->id;
            }
        }

        // Filtrar metadatos no vacíos
        $cleanMeta = collect($this->metaValues)
            ->filter(fn($val) => !is_null($val) && trim((string) $val) !== '')
            ->toArray();

        $procedure = Procedure::create([
            'user_id' => Auth::id(),
            'origin_id' => $this->origin_id,
            'sector_id' => $this->sector_id,
            'procedure_type_id' => $this->procedure_type_id,
            'initial_procedure_type_id' => $this->procedure_type_id,
            'device_id' => $this->device_id ?: null,
            'patrol_assignment_id' => $activeAssignmentId,
            'landmark_id' => $this->landmark_id ?: null,
            'formatted_address' => $this->formatted_address,
            'exact_address' => $this->exact_address,
            'address_reference' => $this->address_reference,
            'metadata' => !empty($cleanMeta) ? $cleanMeta : null,
            'latitude' => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
            'is_preventive' => $this->is_preventive,
            'caller_name' => $this->caller_name,
            'caller_id_document' => $this->caller_id_document,
            'caller_phone' => $this->caller_phone,
            'status' => $status,
            'dispatched_at' => $dispatchedAt,
            'call_id' => $this->call_id,
        ]);

        $logDescription = 'Procedimiento ingresado. ' . ($this->device_id ? 'Móvil despachado inmediatamente.' : 'A la espera de asignación de móvil.');
        if (!empty(trim($this->initial_description))) {
            $logDescription .= ' Relato inicial: "' . trim($this->initial_description) . '".';
        }
        if (!empty($cleanMeta)) {
            $metaSummary = collect($procedure->formatted_metadata)->map(fn($m) => "{$m['label']}: {$m['value']}")->join(' | ');
            $logDescription .= " [Metadatos iniciales: {$metaSummary}]";
        }

        ProcedureLog::create([
            'procedure_id' => $procedure->id,
            'user_id' => Auth::id(),
            'action_type' => 'Ingreso de Llamado',
            'description' => $logDescription,
        ]);

        \App\Models\AuditLog::record(
            'procedure.created',
            'Procedimientos',
            "Ingreso de Procedimiento Folio #{$procedure->id} en {$procedure->exact_address} (Estado: {$procedure->status}).",
            $procedure,
            [
                'sector_id' => $procedure->sector_id,
                'procedure_type_id' => $procedure->procedure_type_id,
                'device_id' => $procedure->device_id,
                'metadata' => $cleanMeta,
            ]
        );

        // Y muy importante: actualizamos la tabla de llamadas para que ella también sepa su caso
        if ($this->call_id) {
            \App\Models\Call::where('id', $this->call_id)->update(['procedure_id' => $procedure->id]);
        }

        $this->closeModal();
        $this->dispatch('procedure-created'); 
    }

    public function render()
    {
        return view('livewire.create-procedure');
    }
}
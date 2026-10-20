<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sector;
use App\Models\ProcedureType;
use App\Models\Device;
use App\Models\Origin;
use App\Models\EventCategory;
use App\Models\SystemCatalogItem;
use App\Models\AuditLog;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ManageCatalogs extends Component
{
    public $activeTab = 'sectors'; // Pestaña por defecto

    // Variables del formulario general
    public $name = '';
    public $editingId = null;

    // Variables extra para Categorías / Etiquetas con color e icono
    public $icon = 'fa-tag';
    public $color = 'blue';
    public $code = '';
    public $sort_order = 1;
    public $is_active = true;

    // Variables extra para Bloques Horarios de Turnos de Operadores (operator_shift_blocks)
    public $shift_start_time = '08:00';
    public $shift_end_time = '14:00';
    public $min_phone_ops = 2;
    public $min_camera_ops = 4;

    // Variables extra para Campos Meta de Tipos de Procedimiento (procedure_types)
    public $metaFields = [];
    public $newFieldLabel = '';
    public $newFieldType = 'text';
    public $newFieldPlaceholder = '';
    public $newFieldOptions = '';

    public function mount()
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
    }

    public function isSystemCatalogGroup(): bool
    {
        return array_key_exists($this->activeTab, SystemCatalogItem::GROUPS);
    }

    private function getModelClass(): string
    {
        if ($this->isSystemCatalogGroup()) {
            return SystemCatalogItem::class;
        }

        return match ($this->activeTab) {
            'sectors' => Sector::class,
            'procedure_types' => ProcedureType::class,
            'devices' => Device::class,
            'origins' => Origin::class,
            'event_categories' => EventCategory::class,
            default => Sector::class,
        };
    }

    private function getTableName(): string
    {
        return match ($this->activeTab) {
            'sectors' => 'sectors',
            'procedure_types' => 'procedure_types',
            'devices' => 'devices',
            'origins' => 'origins',
            'event_categories' => 'event_categories',
            default => 'system_catalog_items',
        };
    }

    public function switchTab($tab)
    {
        $validTabs = array_merge(
            ['sectors', 'procedure_types', 'event_categories', 'devices', 'origins'],
            array_keys(SystemCatalogItem::GROUPS)
        );

        if (!in_array($tab, $validTabs, true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->name = '';
        $this->editingId = null;
        $this->icon = $this->activeTab === 'event_categories' ? '📌' : 'fa-tag';
        $this->color = 'blue';
        $this->code = '';
        $this->sort_order = 1;
        $this->is_active = true;
        $this->shift_start_time = '08:00';
        $this->shift_end_time = '14:00';
        $this->min_phone_ops = 2;
        $this->min_camera_ops = 4;
        $this->metaFields = [];
        $this->resetNewFieldInputs();
        $this->resetValidation();
    }

    public function resetNewFieldInputs()
    {
        $this->newFieldLabel = '';
        $this->newFieldType = 'text';
        $this->newFieldPlaceholder = '';
        $this->newFieldOptions = '';
    }

    public function addMetaField()
    {
        $label = trim($this->newFieldLabel);
        if ($label === '') {
            $this->addError('newFieldLabel', 'Ingrese el nombre o etiqueta del campo meta.');
            return;
        }

        $key = Str::slug($label, '_');
        if ($key === '') {
            $key = 'campo_' . (count($this->metaFields) + 1);
        }

        $existingKeys = collect($this->metaFields)->pluck('key')->toArray();
        if (in_array($key, $existingKeys)) {
            $key .= '_' . (count($this->metaFields) + 1);
        }

        $this->metaFields[] = [
            'key' => $key,
            'label' => $label,
            'type' => $this->newFieldType,
            'placeholder' => trim($this->newFieldPlaceholder),
            'options' => trim($this->newFieldOptions),
        ];

        $this->resetNewFieldInputs();
    }

    public function removeMetaField($index)
    {
        if (isset($this->metaFields[$index])) {
            unset($this->metaFields[$index]);
            $this->metaFields = array_values($this->metaFields);
        }
    }

    public function applyMetaPreset($presetKey)
    {
        $presets = ProcedureType::getPresetTemplates();
        if (isset($presets[$presetKey])) {
            $this->metaFields = $presets[$presetKey]['fields'];
        }
    }

    public function save()
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $this->name = trim($this->name);
        $tableName = $this->getTableName();

        // Reglas de validación estrictas con unicidad por tabla o por grupo de catálogo
        if ($this->isSystemCatalogGroup()) {
            $uniqueRule = Rule::unique('system_catalog_items', 'name')
                ->where(fn($q) => $q->where('catalog_group', $this->activeTab));
            if ($this->editingId) {
                $uniqueRule->ignore($this->editingId);
            }

            $rules = [
                'name' => ['required', 'string', 'min:2', 'max:150', $uniqueRule],
                'color' => ['required', 'string', 'max:30'],
                'icon' => ['nullable', 'string', 'max:60'],
                'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            ];

            if ($this->activeTab === 'operator_shift_blocks') {
                $rules['shift_start_time'] = ['required', 'regex:/^\d{2}:\d{2}$/'];
                $rules['shift_end_time'] = ['required', 'regex:/^\d{2}:\d{2}$/'];
                $rules['min_phone_ops'] = ['required', 'integer', 'min:0', 'max:30'];
                $rules['min_camera_ops'] = ['required', 'integer', 'min:0', 'max:50'];
            }
        } else {
            $uniqueRule = Rule::unique($tableName, 'name');
            if ($this->editingId) {
                $uniqueRule->ignore($this->editingId);
            }

            $rules = [
                'name' => ['required', 'string', 'min:2', 'max:150', $uniqueRule],
            ];

            if ($this->activeTab === 'event_categories') {
                $rules['icon'] = ['required', 'string', 'max:20'];
                $rules['color'] = ['required', 'string', 'max:30'];
            }
        }

        $this->validate($rules, [
            'name.required' => 'El nombre del registro es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.unique' => 'Ya existe un registro con este mismo nombre en este catálogo.',
            'shift_start_time.regex' => 'El formato de hora de inicio debe ser HH:MM (ej: 08:00).',
            'shift_end_time.regex' => 'El formato de hora de término debe ser HH:MM (ej: 14:00).',
            'min_phone_ops.integer' => 'La dotación de operadores telefónicos debe ser un número entero.',
            'min_camera_ops.integer' => 'La dotación de operadores de cámaras debe ser un número entero.',
        ]);

        $modelClass = $this->getModelClass();

        if ($this->isSystemCatalogGroup()) {
            $meta = null;
            if ($this->activeTab === 'operator_shift_blocks') {
                $meta = [
                    'start_time' => $this->shift_start_time,
                    'end_time' => $this->shift_end_time,
                    'min_phone_ops' => (int) $this->min_phone_ops,
                    'min_camera_ops' => (int) $this->min_camera_ops,
                ];
            }

            $data = [
                'catalog_group' => $this->activeTab,
                'name' => $this->name,
                'code' => $this->code ?: strtoupper(Str::slug($this->name, '_')),
                'icon' => $this->icon ?: 'fa-tag',
                'color' => $this->color ?: 'blue',
                'meta' => $meta,
                'sort_order' => (int) $this->sort_order,
                'is_active' => (bool) $this->is_active,
            ];
        } else {
            $data = ['name' => $this->name];

            if ($this->activeTab === 'event_categories') {
                $data['icon'] = $this->icon ?: '📌';
                $data['color'] = $this->color ?: 'blue';
                $data['is_active'] = (bool) $this->is_active;
            } elseif ($this->activeTab === 'procedure_types') {
                $data['meta_fields'] = !empty($this->metaFields) ? array_values($this->metaFields) : null;
            }
        }

        if ($this->editingId) {
            $item = $modelClass::findOrFail($this->editingId);
            $item->update($data);
            AuditLog::record(
                'catalog.updated',
                'Catálogos',
                "Actualización en catálogo [{$this->activeTab}]: {$item->name}",
                $item
            );
            session()->flash('message', 'Registro actualizado correctamente.');
        } else {
            $item = $modelClass::create($data);
            AuditLog::record(
                'catalog.created',
                'Catálogos',
                "Creación en catálogo [{$this->activeTab}]: {$item->name}",
                $item
            );
            session()->flash('message', 'Registro creado exitosamente.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $modelClass = $this->getModelClass();
        $item = $modelClass::find($id);
        if (!$item) {
            return;
        }

        $this->editingId = $item->id;
        $this->name = $item->name;

        if ($this->isSystemCatalogGroup()) {
            $this->icon = $item->icon ?: 'fa-tag';
            $this->color = $item->color ?: 'blue';
            $this->code = $item->code ?? '';
            $this->sort_order = $item->sort_order ?? 1;
            $this->is_active = (bool) $item->is_active;

            if ($this->activeTab === 'operator_shift_blocks' && is_array($item->meta)) {
                $this->shift_start_time = $item->meta['start_time'] ?? '08:00';
                $this->shift_end_time = $item->meta['end_time'] ?? '14:00';
                $this->min_phone_ops = $item->meta['min_phone_ops'] ?? 2;
                $this->min_camera_ops = $item->meta['min_camera_ops'] ?? 4;
            }
        } elseif ($this->activeTab === 'event_categories') {
            $this->icon = $item->icon ?: '📌';
            $this->color = $item->color ?: 'blue';
            $this->is_active = (bool) ($item->is_active ?? true);
        } elseif ($this->activeTab === 'procedure_types') {
            $this->metaFields = is_array($item->meta_fields) && !empty($item->meta_fields)
                ? $item->meta_fields
                : $item->effective_meta_fields;
        }
    }

    public function toggleItemActive($id)
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $modelClass = $this->getModelClass();
        $item = $modelClass::find($id);
        if ($item && isset($item->is_active)) {
            $item->update(['is_active' => !$item->is_active]);
            session()->flash('message', 'Estado del registro actualizado.');
        }
    }

    public function delete($id)
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);

        $modelClass = $this->getModelClass();
        try {
            $item = $modelClass::find($id);
            if ($item) {
                $itemName = $item->name;
                $item->delete();
                AuditLog::record(
                    'catalog.deleted',
                    'Catálogos',
                    "Eliminación en catálogo [{$this->activeTab}]: {$itemName}"
                );
                session()->flash('message', 'Registro eliminado.');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            session()->flash('error', 'No se puede eliminar este registro porque está asociado a procedimientos o turnos existentes.');
        }
    }

    public function render()
    {
        if ($this->isSystemCatalogGroup()) {
            $items = SystemCatalogItem::where('catalog_group', $this->activeTab)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        } else {
            $modelClass = $this->getModelClass();
            $items = $modelClass::orderBy('name')->get();
        }

        return view('livewire.manage-catalogs', [
            'items' => $items,
            'presets' => ProcedureType::getPresetTemplates(),
            'systemGroups' => SystemCatalogItem::GROUPS,
        ])->layout('layouts.app');
    }
}
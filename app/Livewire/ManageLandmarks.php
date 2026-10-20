<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Landmark;

class ManageLandmarks extends Component
{
    // Variables del formulario
    public $name = '';
    public $address = '';
    public $latitude = '';
    public $longitude = '';
    public $editingId = null;

    public function mount()
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403);
    }

    public function resetForm()
    {
        $this->reset(['name', 'address', 'latitude', 'longitude', 'editingId']);
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($this->editingId) {
            $landmark = Landmark::find($this->editingId);
            $landmark->update([
                'name' => $this->name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);
            session()->flash('message', 'Hito actualizado correctamente.');
        } else {
            Landmark::create([
                'name' => $this->name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ]);
            session()->flash('message', 'Hito creado exitosamente.');
        }

        $this->resetForm();
    }

    public function edit($id)
    {
        $landmark = Landmark::find($id);
        $this->editingId = $landmark->id;
        $this->name = $landmark->name;
        $this->address = $landmark->address;
        $this->latitude = $landmark->latitude;
        $this->longitude = $landmark->longitude;
    }

    public function delete($id)
    {
        Landmark::destroy($id);
        session()->flash('message', 'Hito eliminado.');
    }

    public function render()
    {
        $landmarks = Landmark::orderBy('name')->get();
        
        return view('livewire.manage-landmarks', [
            'landmarks' => $landmarks
        ])->layout('layouts.app');
    }
}
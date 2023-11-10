<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

use App\Models\Instance;

class ChatConsole extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(){}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $instances = $this->getInstances();
        return view('components.chat-console', ['instances' => $instances]);
    }

    public function getInstances(): Collection
    {
        return Instance::where('status', Instance::STATUS_UP)->get();
    }
}

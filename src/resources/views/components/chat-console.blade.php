

@foreach ($instances as $instance)
    
    <div class="window" style="margin: 32px; width: 100%">
        <div class="title-bar">
        <div class="title-bar-text">
            Chat Instance: {{ $instance->client->nick->nick }} @ {{ $instance->client->network->name }}
        </div>

        <div class="title-bar-controls">
            <button aria-label="Minimize"></button>
            <button aria-label="Maximize"></button>
            <button aria-label="Close"></button>
        </div>
        </div>
        <div class="window-body">
        <div class="field-row-stacked" style="width: 100%">
            <label for="text20">Chat Text Here</label>
            <textarea id="text20" rows="30"></textarea>
        </div>
        <section class="field-row" style="justify-content: flex-end">
            <button>OK</button>
            <button>Cancel</button>
        </section>
        </div>
    </div>

@endforeach

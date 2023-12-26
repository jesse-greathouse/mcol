<div class="window " style="width: 100%">
    <div class="title-bar">
        <div class="title-bar-text">
            Mcol Chat Console
        </div>
    </div>
    <div class="window-body">
        <menu role="tablist">
        @foreach($instances as $instance)
            @php $selected=''; @endphp
            @if ($loop->first)
                @php $selected='aria-selected=true'; @endphp
            @endif
            <li role="tab" {{ $selected }} id="tab-instance-{{ $instance->id }}">{{ $instance->client->network->name }}</li>
        @endforeach
        </menu>

        @foreach ($instances as $instance)
        <div class="field-row-stacked" style="width: 100%">
            <textarea id="main-console-{{ $instance->id }}" rows="30"></textarea>
            <textarea id="chat-console-{{ $instance->id }}" rows="30"></textarea>
        </div>
        @endforeach

        <section class="field-row" style="justify-content: flex-end">
            <button>OK</button>
            <button>Cancel</button>
        </section>
    </div>
</div>

<div class="{{ $classes['wrapper'] ?? '' }}">
    {{-- Delete modal --}}
    <form wire:submit.prevent="deleteSelected">
        <x-senna.modal.confirm wire:model.defer="showDeleteModal">
            <x-slot name="title">
                {{ sprintf(__('Delete %d %s'), count($selected), count($selected) === 1 ? __("item") : __("items")) }}
            </x-slot>

            <x-slot name="content">
                {{ sprintf(__('Are you sure you want to delete the selected %s'), count($selected) === 1 ? __("item") : __("items")) }}
            </x-slot>

            <x-slot name="footer">
                <x-senna.button.white wire:click="$set('showDeleteModal', false)" wire:loading.attr="disabled">{{ __('Cancel') }}</x-senna.button.white>
                <x-senna.button.red type="submit">{{ __('Yes') }}</x-senna.button.red>
            </x-slot>

        </x-senna.modal.confirm>
    </form>

  
    <div class="top-header flex space-x-2 items-center">
        <div>
            <x-senna.delegate name="title" :delegate="$delegate['_class']"></x-senna.delegate>
        </div>

        <div class="flex !ml-auto">
            @if($showFilters)
            {{-- Add filter --}}
            <x-senna.dropdown :closeOnInnerclick="false" :open="false" align="right" widthClass="" contentClass="shadow-lg bg-white flex border !ring-0">
                <x-slot name="trigger">
                    <x-senna.button class="shadow-none" colorClass="text-black bg-transparent pr-2 pl-0 focus:ring-0">
                        {{-- <x-heroicon-s-adjustments class="w-5"></x-heroicon-s-adjustments> --}}
                        {{-- <x-heroicon-s-chevron-down class="w-5"></x-heroicon-s-chevron-down> --}}
                        <span>{{ __('Filters') }}</span>
                    </x-senna.button>
                </x-slot>
                <x-slot name="content">
                    @if($newFilterProp)
                    {{-- Dropdown left --}}
                    <div class="flex-grow w-72 p-4">
                        <form wire:submit.prevent="setNewFilter">
                        <x-senna.input.select-native class="mb-2" inputClass="!border-none focus:!ring-0 mb-2" wire:model="newFilterOperator">
                            <option value="LIKE">{{ __('Contains') }}</option>
                            <option value="=">{{ __('Is equal') }}</option>
                            <option value="<>">{{ __('Is not') }}</option>
                            <option value=">">{{ __('Is greater than') }}</option>
                            <option value=">=">{{ __('Is greater than or equal') }}</option>
                            <option value="<">{{ __('Is smaller than') }}</option>
                            <option value="<=">{{ __('Is smaller than or equal') }}</option>
                        </x-senna.input.select-native>

                        <x-senna.input wire:model="newFilterCondition" class="mb-2" prefixClass="z-10 !pl-1 !pointer-events-auto"></x-senna.input>
                        <div class="mt-4 flex">
                            <x-senna.button.gray x-on:click="open=false">
                                {{ __('Cancel') }}
                            </x-senna.button.gray>
                            <x-senna.button x-on:click="open=false" colorClass="text-white bg-primary-color ring-primary-color" type="submit" class="ml-auto">
                                {{ __('Apply') }}
                            </x-senna.button>
                        </div>
                        </form>
                    </div>
                    @endif
                    {{-- Dropdown right --}}
                    <div class="overflow-y-auto max-h-[200px] bg-gray-50 rounded-md flex-shrink-0 w-42 flex flex-col items-start">
                        @foreach($this->searchableProps as $prop)
                            <x-senna.dropdown.item tag="button" 
                                wire:click="$set('newFilterProp', '{{ $prop }}')"   
                                class="{{ $newFilterProp == $prop ? '!bg-primary-color !text-white' : '' }} font-semibold 
                                hover:!bg-primary-color-80 hover:!text-white focus:!bg-primary-color focus:!text-white">
                                {{ $this->labels[$prop] ?? $prop }}
                            </x-senna.dropdown.item>
                        @endforeach
                    </div>
                    
                </x-slot>
            </x-senna.dropdown>
            @endif
            
            @if($showSearch)
            <x-senna.input wire:model="search" 
                placeholder="Search in table" 
                shortcut="cmd.f"
                style="transition-property: width;" 
                inputClass="cursor-pointer !pl-12 focus:!w-52 w-7 {{ strlen($search) > 0 ? '!w-52' : '' }} pr-0 focus:!ring-0 transition-all !border-none !bg-transparent">
                <x-slot name="prefix">
                    <x-heroicon-s-search class="w-5"></x-heroicon-s-search>
                </x-slot>
            </x-senna.input>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    @if(count($this->filters) > 0)
    <div class="filter-display pb-5 flex flex-wrap">
        @foreach($this->filters as $index => $item)
            @php
                $prop = $item[0];
                $compare = $item[1] === "LIKE" ? "contains" : $item[1];
                $value = str_replace("%", "", $item[2])
            @endphp
            <x-senna.tag.gray class="mr-2 mb-2 shadow-lg flex flex-grow-0 items-center !p-2 space-x-1" colorClass="bg-primary-color text-white">
                <span>{{ $prop }}</span>
                <span class="opacity-50">{{ $compare }}</span>
                <span>{{ $value }}</span>
                <button wire:click="removeFilter({{ $index }})">
                    <x-heroicon-s-x class="w-4"></x-heroicon-s-x>
                </button>
            </x-senna.tag.gray>
        @endforeach
    </div>
    @endif

    {{-- Table --}}
    <div class='main-table overflow-x-auto shadow-xl border rounded-md border-gray-200 {{ $classes['table-wrapper'] ?? '' }}'>
        <x-senna.table class="w-full {{ $classes['table'] ?? '' }}">
            @if($showHeaders)
            {{-- Head --}}
            <thead class="bg-gray-50">
                <x-senna.table.heading class="w-4">
                    <x-senna.input.checkbox wire:model="selectPage"></x-senna.input.checkbox>
                </x-senna.table.heading>
                @foreach($cols as $col)
                    @if( !($col['visible'] ?? true) ) @continue @endif
                    @php
                        $sortBy = ($col['sortable'] ?? null) ? (is_string($col['sortable']) ? $col['sortable'] : $col['prop']) : null;
                        $headerClass = ($classes['header'] ?? '') . ' ' .  ($col['classes']['header'] ?? '');
                    @endphp

                    <x-senna.table.heading :sticky="$col['sticky'] ?? false" :sortBy="$sortBy" :sortField="$sortField" :sortDirection="$sortDirection" class="font-semibold {{ $headerClass }}" width="{{ $col['width'] ?? '' }}">
                        <x-senna.delegate name="header:{prop}" :data="['col' => $col, 'prop' => $col['prop']]" :delegate="$delegate['_class']">
                            {{ $col["label"] }}
                        </x-senna.delegate>
                    </x-senna.table.heading>
                @endforeach
            </thead>
            @endif
            <x-senna.table.body>
                @if($this->hasSelection)
                {{-- Selector --}}
                <x-senna.table.selector class="{{ $classes['selector'] ?? '' }}">
                    <div class="flex flex-row">
                        <div class="flex-grow inline-flex space-x-1">
                            @if($showSelectedCount)
                            <span>{{ sprintf(__('%d of %d items selected'), $this->selectedCount, $this->rows->total()) }}.</span>
                            @endif
                            {{-- @if(!$this->selectAll) --}}
                                <x-senna.button.text wire:click="selectAll">{{ __('Select all') }}</x-senna.button.text>
                            {{-- @endif --}}
                            <span>|</span>
                            <x-senna.dropdown>
                                <x-slot name="trigger">
                                    <x-senna.button.text class="inline-flex space-x-2">
                                        <span>{{ __('with selected') }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </x-senna.button.text>
                                </x-slot>

                                <x-slot name="content">
                                    <x-senna.dropdown.item class="flex space-x-2 items-center" tag="button" wire:click="$toggle('showDeleteModal')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>{{ __('Delete') }}</span>
                                    </x-senna.dropdown.item>
                              
                                    <x-senna.delegate name="withSelected" :delegate="$delegate['_class']" />
                                </x-slot>
                            </x-senna.dropdown>

                        </div>

                        <x-senna.button.text wire:click="deselectAll" class="text-gray-900">
                          <svg class="ml-auto w-6" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                          </svg>
                        </x-senna.button.text>
                    </div>
                </x-senna.table.selector>
                @endif

                @forelse($this->rows as $key => $row)
                {{-- Rows --}}
                <x-senna.table.row wire:key="row-{{ $row->id }}" :isSelected="$this->isSelected($row->id)" :class="$classes['row'] ?? ''">
                    @php
                        $isSelected = $this->isSelected($row->id);
                    @endphp
                    <x-senna.table.cell class="pl-6 pr-0 py-4 relative {{ $classes['select'] ?? '' }}">
                        @if($isSelected)
                        <div class="bg-primary-color absolute inset-y-0 w-1 left-0"></div>
                        @endif
                        <x-senna.input.checkbox wire:key="check-{{ $row->id }}" value="{{ $row->id }}" wire:model="selected"/>
                    </x-senna.table.cell>
                    @foreach($cols as $col)
                        @if( !($col['visible'] ?? true) ) @continue @endif
                        <x-senna.table.cell :sticky="$col['sticky'] ?? false" :isSelected="$isSelected" :class="($col['classes']['cell'] ?? '').' '.($classes['cell'] ?? '')">
                            @if($col['component'] ?? null)
                                <x-senna.dynamic :component="$col['component']" :data="['row' => $row, 'prop' => $col['prop'], 'col' => $col]"></x-senna.dynamic>
                            @else
                                <x-senna.delegate name="row:{prop}" :data="['row' => $row, 'prop' => $col['prop'], 'col' => $col]" :delegate="$delegate['_class']">
                                    @if($col['editable'])
                                    <input wire:model.lazy="{{ str_replace("*", $key, $col['wireModel']) }}" />
                                    @else
                                    <div>
                                        @php
                                        $data = eloquent_via_dot($row, $col['prop'])
                                        @endphp
                                        @if(is_iterable($data))
                                            <div class="flex space-x-1">
                                            @foreach($data as $item)
                                                <x-senna.tag.primary class="text-xs">
                                                    {{ $item }}
                                                </x-senna.tag.primary>
                                            @endforeach
                                            </div>
                                        @else
                                        {{ $data}}
                                        @endif
                                    </div>
                                    @endif
                                </x-senna.delegate>
                            @endif
                        </x-senna.table.cell>
                    @endforeach
                </x-senna.table.row>
                @empty
                {{-- No results --}}
                <x-senna.table.row>
                    <x-senna.table.cell colspan="100%">
                        <div class="text-lg py-10 flex flex-col items-center space-y-2">
                            <x-heroicon-o-emoji-sad class="w-12"></x-heroicon-o-emoji-sad>
                            <div>
                                {{ __('No items found') }}
                            </div>
                        </div>
                    </x-senna.table.cell>
                </x-senna.table.row>
                @endforelse
            </x-senna.table.body>
        </x-senna.table>
    </div>

    @if($this->rows->hasPages())
    {{-- Pagination --}}
    <div class="pagination mt-6 w-full mb-5 {{ $classes['pagination'] ?? '' }}">
        {{ $this->rows->links('senna::livewire.pagination') }}
    </div>
    @endif
</div>

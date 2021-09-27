<?php


namespace Senna\Admin\Http\Livewire\Components;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Senna\Admin\Http\Livewire\Traits\WithBulkActions;
use Senna\Admin\Http\Livewire\Traits\WithCachedRows;
use Senna\Admin\Http\Livewire\Traits\WithDelegate;
use Senna\Admin\Http\Livewire\Traits\WithPerPagePagination;
use Senna\Admin\Http\Livewire\Traits\WithSorting;

use Livewire\Component;

class Datatable extends Component
{
    public static $currentlyRendering = null;

    use WithBulkActions;
    use WithPerPagePagination;
    use WithCachedRows;
    use WithSorting;
    use WithDelegate;

    public $identifier = "";

    public $cols = [];
    public $showDeleteModal = false;
    public $with = null;

    public $newFilterProp = "";
    public $newFilterCondition = "";
    public $newFilterOperator = "LIKE";

    public $model;
    public $searchHasFocus = false;

    public $filters = [];
    public $search = '';
    public $class = "";

    public $classes = [];
    public $labels = [];

    public $fastEdit = false;
    public $fastEditing = [];

    public $showSearch = true;
    public $showFilters = true;
    public $showHeaders = true;
    public $showSelectedCount = true;

    public $queryString = [
        'search' => ['except' => ''],
        'filters' => ['except' => []],
        'sortField' => ['except' => ''],
        'sortDirection' => ['except' => 'asc']
    ];

    public function rules() {
        $rules = ['selected' => ''];

        foreach($this->cols as $col) {
            if (!$col['wireModel']) continue;

            $rules[$col['wireModel']] = $col['rules'];
        }

        return $rules;
    }

    public function render()
    {
        return view('senna::livewire.components.datatable');
    }

    public function mount($delegate, $labels = [], $cols = [], $model = null) {
        $this->model = $model;

        $this->cols = [];

        foreach($cols as $key => &$col) {
            if ($col['disabled'] ?? false) continue;

            $col = is_string($col) ? [
                'label' => $col,
            ] : $col;
            
            $col['prop'] = $prop = $col['prop'] ?? $key;

            $col['editable'] = $editable = $col['editable'] ?? false;

            $col['wireModel'] =  $editable ? "fastEditing.*." . $prop : false;
            $col['rules'] = is_array($editable) ? $editable : ['required'];

            $this->labels[$prop] = $col['label'] ?? $prop;

            $this->cols[] = $col;
        }

        $this->labels = array_merge($this->labels, $labels);
    }

    protected function getListeners()
    {
        $id = $this->identifier ? $this->identifier . ':' : '';
        return [
            'datatable:' . $id . 'setFilters' => 'setFilters',
            'datatable:' . $id . 'setSearch' => 'setSearch',
            'datatable:' . $id . 'setSelected' => 'setSelected',
            'datatable:' . $id . 'refresh' => '$refresh',
        ];
    }

    public function removeFilter($index) {
        unset($this->filters[$index]);
    }

    public function setFilters($filters) {
        $this->filters = $filters;
    }
    public function setSearch($search) {
        $this->search = $search;
    }
    public function setSelected($array) {
        $this->selected = $array;
    }

    public function setNewFilter() {

        $this->filters[] = [
            $this->newFilterProp,
            $this->newFilterOperator,
            $this->newFilterCondition
        ];

        $this->newFilterProp = "";
        $this->newFilterCondition = "";
    }

    public function applyQueryFilter($query, $method, $fallback) {
        $result = $this->delegateAction($method, [$query, $this], false);

        if ($result) {
            return $result;
        }
        return $fallback($query);
    }

    public function applyQueryFilters($query) {
        $id = $this->identifier ? $this->identifier . ':' : '';

        $query = $this->applyQueryFilter($query, 'datatable:' . $id . 'query', fn($query) => $query);
        $query = $this->applyQueryFilter($query, 'datatable:' . $id . 'filters', function($query) {
            foreach($this->filters as $where) {
                $query->dt_search([$where[0]], $where[2], $where[1], $query);
            }
            return $query;
        });
        $query = $this->applyQueryFilter($query, 'datatable:' . $id . 'search', function($query) {
            return $query->dt_search($this->searchableProps, $this->search);
        });
        $query = $this->applyQueryFilter($query, 'datatable:' . $id . 'sorting', fn($query) => $this->applySorting($query));

        return $query;
    }

    public function getRowsQueryProperty() {
        $query = $this->model::query();
        if ($this->with) {
            $query = $query->with($this->with);
        }
        return $this->applyQueryFilters($query);
    }

    public function updated($item, $value) {
        if (str_contains($item, "fastEditing")) {
            $this->validate();

            foreach($this->fastEditing as $item) {
                if($item->isDirty()) {
                    $item->save();
                }
            }
        }

        if ($item === "selected") {
            $id = $this->identifier ? $this->identifier . ':' : '';

            $this->emit('datatable:' . $id . "selected", $this->selected);
        }
    }

    public function getRowsProperty() {
     
        $rows = $this->applyPagination(
            $this->rowsQuery
        );

        $this->fastEditing = new EloquentCollection($rows->all());

        return $rows;
    }

    public function getSearchablePropsProperty() {
        return collect($this->cols)
            ->filter(fn($x) => $x['searchable'] ?? null)
            ->map(function($x) {
                if (is_bool($x['searchable'])) {
                    return $x['prop'];
                }
                return $x['searchable'];
            })
            ->flatten()
            ->toArray();
    }

    public function delete($id) {
        $this->addToSelection(strval($id));
        $this->showDeleteModal = true;
    }

    public function deleteSelected() {
        $this->selectedRows = $this->delegateAction('datatable:delete', [$this->selectedRowsQuery->get(), $this], true);

        foreach($this->selectedRows as $row) {
            $row->delete();
        }

        $this->selected = [];
        $this->selectPage = false;
        $this->showDeleteModal = false;
    }

    public function updatedSearch() {
        $this->page = 1;
    }
    public function updatedFilters() {
        $this->page = 1;
    }

}

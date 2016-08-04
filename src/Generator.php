<?php

/*
 * This file is part of Softerize TableList
 *
 * (c) Oscar Dias <oscar.dias@softerize.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Softerize\TableList;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class that creates html tables with buttons, search, page size and pagination.
 *
 * @author Oscar Dias <oscar.dias@softerize.om>
 */
class Generator
{
    /**
     * Model being used
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * Query builder
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Options object
     *
     * @var Options
     */
    protected $options;

    /**
     * Current request object
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Original request object
     *
     * @var \Softerize\TableList\RequestData
     */
    protected $requestData;

    /**
     * Create a new TableList instance.
     *
     * Check the possible options at Softerize\TableList\Options.
     *
     * @param mixed $query
     * @param \Illuminate\Http\Request  $request
     * @param array $options
     * @return void
     */
    public function __construct($query, Request $request, array $options = [])
    {
        $fields = ( isset($options['fields']) ? $options['fields'] : '*' );

        // Get query and model
        if($query instanceof Builder) {
            // $query is a instance of the Builder class
            $this->query = $query;
            $this->model = $this->query->getModel();

            // Set correct fields from the query
            if($fields === '*') {
                $fields = $query->getQuery()->columns;
            }
        } elseif($query instanceof Model) {
            // $query is a instance of the Model class
            $this->model = $query;
            $this->query = $query->select($fields);

            // Set correct fields from the model
            if($fields === '*') {
                $fields = \DB::getSchemaBuilder()->getColumnListing($this->model->getTable());
            }
        } else {
            // $query is a string pointint to the model
            $this->model = new $query;
            $this->query = $this->model->select($fields);

            // Set correct fields from the model
            if($fields === '*') {
                $fields = \DB::getSchemaBuilder()->getColumnListing($this->model->getTable());
            }
        }

        // Request
        $this->request = $request;

        // Options
        $url = ( isset($options['url']) ? $options['url'] : $request->path() );

        $this->options = new Options([
            'url'          => $url,
            'id'           => ( isset($options['id']) ? $options['id'] : 'tl_' . str_replace('/', '_', $url) ),
            'fields'       => $fields,
            'queryString'  => ( isset($options['queryString']) ? $options['queryString'] : [] ),
            'sort'         => ( isset($options['sort']) ? $options['sort'] : '' ),
            'sortOrder'    => ( isset($options['sortOrder']) ? $options['sortOrder'] : 'asc' ),
            'buttons'      => ( isset($options['buttons']) ? $options['buttons'] : [] ),
            'rowActions'   => ( isset($options['rowActions']) ? $options['rowActions'] : [] ),
            'noEntriesMsg' => ( isset($options['noEntriesMsg']) ? $options['noEntriesMsg'] : FALSE )
        ]);

        // Add the ID to the query string
        if($this->options->id)
        {
            $this->options->queryString['id'] = $this->options->id;
        }

        // Use singleton to store the original request data
        $this->requestData = RequestData::getInstance();
        if($this->requestData->isEmpty()) {
            $this->requestData->setData($request->all());
        }
    }

    /**
     * Generates the HTML for the table list
     *
     * @return string
     */
    public function generate()
    {
        // Get filter values for the current list
        if($this->request->get('id') == $this->options->id) {
            // Get attributes from the original request
            $original = $this->requestData->getData();

            // Store table id in the session
            $this->request->session()->put($this->options->id, $original);
        } else {
            // Get from session
            $original = $this->request->session()->get($this->options->id);

            if(!$original) {
                // Nothing saved so far, define starting attributes
                $original = [
                    'page' => 1,
                    'ps' => config('table-list.pagination.size', 10),
                    'sf' => $this->options->sort,
                    'so' => $this->options->sortOrder,
                    's' => ''
                ];
            }
        }

        // Check if pagination needs to be reset
        $page = isset($original['pr']) && $original['pr'] ? 1 : $original['page'];

        // Page size
        $previousPageSize = ( isset($original['pps']) ? $original['pps'] : $original['ps'] );
        $pageSize = $original['ps'];

        // Sorting
        $selectedSort = ( isset($original['ss']) ? $original['ss'] : FALSE );
        $sortField = $original['sf'];
        $sortOrder = $original['so'];

        // Search
        $search = $original['s'];

        // Check previous page size
        if($previousPageSize) {
            $first = (($page - 1) * $previousPageSize) + 1;
            $page = ceil($first /  $pageSize);
        }

        // Merge current page for the pagination
        $this->request->merge(array('page' => $page));

        // Checks the sorting
        if($selectedSort)
        {
            if($selectedSort == $sortField)
            {
                $sortOrder = ( $sortOrder == 'asc' ? 'desc' : 'asc');
            }
            else
            {
                $sortField = $selectedSort;
                $sortOrder = 'asc';
            }
        }

        /*
         * Adds to query builder
         */
        // Sorting
        if($sortField) {
            $this->query = $this->query->orderBy($sortField, $sortOrder);
        }

        // Search
        if($search)
        {
            $fields = $this->options->fields;
            $this->query = $this->query->where(function($inner) use ($search, $fields)
                {
                    $this->doSearch($inner, $search, $fields);
                }
            );
        }

        // Paginate results
        $entries = $this->query->paginate($pageSize);

        // Create
        $view = view('table-list::list',
                [
                    'entries' => $entries,
                    'url' => $this->options->url,
                    'id' => $this->options->id,
                    'fields' => $this->options->fields,
                    'noEntriesMsg' => $this->options->noEntriesMsg,
                    'buttons' => $this->options->buttons,
                    'rowActions' => $this->options->rowActions,
                    'queryString' => $this->options->queryString,
                    'search' => $search,
                    'pageSize' => $pageSize,
                    'page' => $page,
                    'sortField' => $sortField,
                    'sortOrder' => $sortOrder
                ])->render();

        return $view;
    }

    /**
     * Add where conditions to the query
     *
     * @param  Builder &$inner
     * @param  string  $search
     * @param  mixes   $fields
     * @return void
     */
    public function doSearch(&$inner, $search, $fields)
    {
        if($fields == '*') {
            return;
        }

        foreach ($fields as $key => $field)
        {
            if(isset($field['search']) && $field['search'] === FALSE)
            {
                // Fields without search
                continue;
            }

            $whereCondition = ( isset($field['name']) ? $field['name'] : $field );

            if($key === 0)
            {
                // First item
                $inner = $inner->where($whereCondition, 'like', '%'.$search.'%');
            }
            else
            {
                $inner = $inner->orWhere($whereCondition, 'like', '%'.$search.'%');
            }
        }
    }
}

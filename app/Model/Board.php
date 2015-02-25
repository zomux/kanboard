<?php

namespace Model;

use SimpleValidator\Validator;
use SimpleValidator\Validators;

/**
 * Board model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class Board extends Base
{
    /**
     * SQL table name
     *
     * @var string
     */
    const TABLE = 'columns';

    /**
     * Get Kanboard default columns
     *
     * @access public
     * @return string[]
     */
    public function getDefaultColumns()
    {
        return array(t('Backlog'), t('Ready'), t('Work in progress'), t('Done'));
    }

    /**
     * Get user default columns
     *
     * @access public
     * @return array
     */
    public function getUserColumns()
    {
        $column_names = explode(',', $this->config->get('board_columns', implode(',', $this->getDefaultColumns())));
        $columns = array();

        foreach ($column_names as $column_name) {

            $column_name = trim($column_name);

            if (! empty($column_name)) {
                $columns[] = array('title' => $column_name, 'task_limit' => 0, 'description' => '');
            }
        }

        return $columns;
    }

    /**
     * Create a board with default columns, must be executed inside a transaction
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @param  array    $columns      Column parameters [ 'title' => 'boo', 'task_limit' => 2 ... ]
     * @return boolean
     */
    public function create($project_id, array $columns)
    {
        $position = 0;

        foreach ($columns as $column) {

            $values = array(
                'title' => $column['title'],
                'position' => ++$position,
                'project_id' => $project_id,
                'task_limit' => $column['task_limit'],
                'description' => $column['description'],
            );

            if (! $this->db->table(self::TABLE)->save($values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Copy board columns from a project to another one
     *
     * @author Antonio Rabelo
     * @param  integer    $project_from      Project Template
     * @return integer    $project_to        Project that receives the copy
     * @return boolean
     */
    public function duplicate($project_from, $project_to)
    {
        $columns = $this->db->table(Board::TABLE)
                            ->columns('title', 'task_limit', 'description')
                            ->eq('project_id', $project_from)
                            ->asc('position')
                            ->findAll();

        return $this->board->create($project_to, $columns);
    }

    /**
     * Add a new column to the board
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @param  string    $title         Column title
     * @param  integer   $task_limit    Task limit
     * @param  string    $description   Column description
     * @return boolean|integer
     */
    public function addColumn($project_id, $title, $task_limit = 0, $description = '')
    {
        $values = array(
            'project_id' => $project_id,
            'title' => $title,
            'task_limit' => intval($task_limit),
            'position' => $this->getLastColumnPosition($project_id) + 1,
        	'description' => $description,
        );

        return $this->persist(self::TABLE, $values);
    }

    /**
     * Update a column
     *
     * @access public
     * @param  integer   $column_id     Column id
     * @param  string    $title         Column title
     * @param  integer   $task_limit    Task limit
     * @param  string    $description   Optional description
     * @return boolean
     */
    public function updateColumn($column_id, $title, $task_limit = 0, $description = '')
    {
        return $this->db->table(self::TABLE)->eq('id', $column_id)->update(array(
            'title' => $title,
            'task_limit' => intval($task_limit),
            'description' => $description,
        ));
    }

    /**
     * Move a column down, increment the column position value
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @param  integer  $column_id    Column id
     * @return boolean
     */
    public function moveDown($project_id, $column_id)
    {
        $columns = $this->db->hashtable(self::TABLE)->eq('project_id', $project_id)->asc('position')->getAll('id', 'position');
        $positions = array_flip($columns);

        if (isset($columns[$column_id]) && $columns[$column_id] < count($columns)) {

            $position = ++$columns[$column_id];
            $columns[$positions[$position]]--;

            $this->db->startTransaction();
            $this->db->table(self::TABLE)->eq('id', $column_id)->update(array('position' => $position));
            $this->db->table(self::TABLE)->eq('id', $positions[$position])->update(array('position' => $columns[$positions[$position]]));
            $this->db->closeTransaction();

            return true;
        }

        return false;
    }

    /**
     * Move a column up, decrement the column position value
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @param  integer  $column_id    Column id
     * @return boolean
     */
    public function moveUp($project_id, $column_id)
    {
        $columns = $this->db->hashtable(self::TABLE)->eq('project_id', $project_id)->asc('position')->getAll('id', 'position');
        $positions = array_flip($columns);

        if (isset($columns[$column_id]) && $columns[$column_id] > 1) {

            $position = --$columns[$column_id];
            $columns[$positions[$position]]++;

            $this->db->startTransaction();
            $this->db->table(self::TABLE)->eq('id', $column_id)->update(array('position' => $position));
            $this->db->table(self::TABLE)->eq('id', $positions[$position])->update(array('position' => $columns[$positions[$position]]));
            $this->db->closeTransaction();

            return true;
        }

        return false;
    }

    /**
     * Get all tasks sorted by columns and swimlanes
     *
     * @access public
     * @param  integer $project_id Project id
     * @return array
     */
    public function getBoard($project_id)
    {
        $swimlanes = $this->swimlane->getSwimlanes($project_id);
        $columns = $this->getColumns($project_id);
        $nb_columns = count($columns);

        for ($i = 0, $ilen = count($swimlanes); $i < $ilen; $i++) {

            $swimlanes[$i]['columns'] = $columns;
            $swimlanes[$i]['nb_columns'] = $nb_columns;
            $swimlanes[$i]['nb_tasks'] = 0;

            for ($j = 0; $j < $nb_columns; $j++) {
                $swimlanes[$i]['columns'][$j]['tasks'] = $this->taskFinder->getTasksByColumnAndSwimlane($project_id, $columns[$j]['id'], $swimlanes[$i]['id']);
                $swimlanes[$i]['columns'][$j]['nb_tasks'] = count($swimlanes[$i]['columns'][$j]['tasks']);
                $swimlanes[$i]['nb_tasks'] += $swimlanes[$i]['columns'][$j]['nb_tasks'];
            }
        }

        return $swimlanes;
    }

    /**
     * Get the total of tasks per column
     *
     * @access public
     * @param  integer   $project_id
     * @param  boolean   $prepend       Prepend default value
     * @return array
     */
    public function getColumnStats($project_id, $prepend = false)
    {
        $listing = $this->db
                        ->hashtable(Task::TABLE)
                        ->eq('project_id', $project_id)
                        ->eq('is_active', 1)
                        ->groupBy('column_id')
                        ->getAll('column_id', 'COUNT(*) AS total');

        return $prepend ? array(-1 => t('All columns')) + $listing : $listing;
    }

    /**
     * Get the first column id for a given project
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @return integer
     */
    public function getFirstColumn($project_id)
    {
        return $this->db->table(self::TABLE)->eq('project_id', $project_id)->asc('position')->findOneColumn('id');
    }

    /**
     * Get the list of columns sorted by position [ column_id => title ]
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @param  boolean  $prepend      Prepend a default value
     * @return array
     */
    public function getColumnsList($project_id, $prepend = false)
    {
        $listing = $this->db->hashtable(self::TABLE)->eq('project_id', $project_id)->asc('position')->getAll('id', 'title');
        return $prepend ? array(-1 => t('All columns')) + $listing : $listing;
    }

    /**
     * Get all columns sorted by position for a given project
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @return array
     */
    public function getColumns($project_id)
    {
        return $this->db->table(self::TABLE)->eq('project_id', $project_id)->asc('position')->findAll();
    }

    /**
     * Get the number of columns for a given project
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @return integer
     */
    public function countColumns($project_id)
    {
        return $this->db->table(self::TABLE)->eq('project_id', $project_id)->count();
    }

    /**
     * Get a column by the id
     *
     * @access public
     * @param  integer  $column_id    Column id
     * @return array
     */
    public function getColumn($column_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $column_id)->findOne();
    }

    /**
     * Get the position of the last column for a given project
     *
     * @access public
     * @param  integer  $project_id   Project id
     * @return integer
     */
    public function getLastColumnPosition($project_id)
    {
        return (int) $this->db
                        ->table(self::TABLE)
                        ->eq('project_id', $project_id)
                        ->desc('position')
                        ->findOneColumn('position');
    }

    /**
     * Remove a column and all tasks associated to this column
     *
     * @access public
     * @param  integer  $column_id    Column id
     * @return boolean
     */
    public function removeColumn($column_id)
    {
        return $this->db->table(self::TABLE)->eq('id', $column_id)->remove();
    }

    /**
     * Validate column modification
     *
     * @access public
     * @param  array   $values           Required parameters to update a column
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateModification(array $values)
    {
        $v = new Validator($values, array(
            new Validators\Integer('task_limit', t('This value must be an integer')),
            new Validators\Required('title', t('The title is required')),
            new Validators\MaxLength('title', t('The maximum length is %d characters', 50), 50),
        ));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }

    /**
     * Validate column creation
     *
     * @access public
     * @param  array   $values           Required parameters to save an action
     * @return array   $valid, $errors   [0] = Success or not, [1] = List of errors
     */
    public function validateCreation(array $values)
    {
        $v = new Validator($values, array(
            new Validators\Required('project_id', t('The project id is required')),
            new Validators\Integer('project_id', t('This value must be an integer')),
            new Validators\Required('title', t('The title is required')),
            new Validators\MaxLength('title', t('The maximum length is %d characters', 50), 50),
        ));

        return array(
            $v->execute(),
            $v->getErrors()
        );
    }
}

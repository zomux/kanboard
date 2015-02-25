<?php

namespace Model;

/**
 * Project analytic model
 *
 * @package  model
 * @author   Frederic Guillot
 */
class ProjectAnalytic extends Base
{
    /**
     * Get tasks repartition
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @return array
     */
    public function getTaskRepartition($project_id)
    {
        $metrics = array();
        $total = 0;
        $columns = $this->board->getColumns($project_id);

        foreach ($columns as $column) {

            $nb_tasks = $this->taskFinder->countByColumnId($project_id, $column['id']);
            $total += $nb_tasks;

            $metrics[] = array(
                'column_title' => $column['title'],
                'nb_tasks' => $nb_tasks,
            );
        }

        if ($total === 0) {
            return array();
        }

        foreach ($metrics as &$metric) {
            $metric['percentage'] = round(($metric['nb_tasks'] * 100) / $total, 2);
        }

        return $metrics;
    }

    /**
     * Get users repartition
     *
     * @access public
     * @param  integer   $project_id    Project id
     * @return array
     */
    public function getUserRepartition($project_id)
    {
        $metrics = array();
        $total = 0;
        $tasks = $this->taskFinder->getAll($project_id);
        $users = $this->projectPermission->getMemberList($project_id);

        foreach ($tasks as $task) {

            $user = isset($users[$task['owner_id']]) ? $users[$task['owner_id']] : $users[0];
            $total++;

            if (! isset($metrics[$user])) {
                $metrics[$user] = array(
                    'nb_tasks' => 0,
                    'percentage' => 0,
                    'user' => $user,
                );
            }

            $metrics[$user]['nb_tasks']++;
        }

        if ($total === 0) {
            return array();
        }

        foreach ($metrics as &$metric) {
            $metric['percentage'] = round(($metric['nb_tasks'] * 100) / $total, 2);
        }

        return array_values($metrics);
    }
}

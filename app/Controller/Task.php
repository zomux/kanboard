<?php

namespace Controller;

use Model\Project as ProjectModel;

/**
 * Task controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class Task extends Base
{
    /**
     * Public access (display a task)
     *
     * @access public
     */
    public function readonly()
    {
        $project = $this->project->getByToken($this->request->getStringParam('token'));

        // Token verification
        if (! $project) {
            $this->forbidden(true);
        }

        $task = $this->taskFinder->getDetails($this->request->getIntegerParam('task_id'));

        if (! $task) {
            $this->notfound(true);
        }

        $this->response->html($this->template->layout('task/public', array(
            'project' => $project,
            'comments' => $this->comment->getAll($task['id']),
            'subtasks' => $this->subtask->getAll($task['id']),
            'links' => $this->taskLink->getLinks($task['id']),
            'task' => $task,
            'columns_list' => $this->board->getColumnsList($task['project_id']),
            'colors_list' => $this->color->getList(),
            'title' => $task['title'],
            'no_layout' => true,
            'auto_refresh' => true,
            'not_editable' => true,
        )));
    }

    /**
     * Show a task
     *
     * @access public
     */
    public function show()
    {
        $task = $this->getTask();
        $subtasks = $this->subtask->getAll($task['id']);

        $values = array(
            'id' => $task['id'],
            'date_started' => $task['date_started'],
            'time_estimated' => $task['time_estimated'] ?: '',
            'time_spent' => $task['time_spent'] ?: '',
        );

        $this->dateParser->format($values, array('date_started'));

        $this->response->html($this->taskLayout('task/show', array(
            'project' => $this->project->getById($task['project_id']),
            'files' => $this->file->getAll($task['id']),
            'comments' => $this->comment->getAll($task['id']),
            'subtasks' => $subtasks,
            'links' => $this->taskLink->getLinks($task['id']),
            'task' => $task,
            'values' => $values,
            'columns_list' => $this->board->getColumnsList($task['project_id']),
            'colors_list' => $this->color->getList(),
            'date_format' => $this->config->get('application_date_format'),
            'date_formats' => $this->dateParser->getAvailableFormats(),
            'title' => $task['project_name'].' &gt; '.$task['title'],
        )));
    }

    /**
     * Display a form to create a new task
     *
     * @access public
     */
    public function create(array $values = array(), array $errors = array())
    {
        $project = $this->getProject();
        $method = $this->request->isAjax() ? 'render' : 'layout';

        if (empty($values)) {

            $values = array(
                'swimlane_id' => $this->request->getIntegerParam('swimlane_id'),
                'column_id' => $this->request->getIntegerParam('column_id'),
                'color_id' => $this->request->getStringParam('color_id'),
                'owner_id' => $this->request->getIntegerParam('owner_id'),
                'another_task' => $this->request->getIntegerParam('another_task'),
            );
        }

        $this->response->html($this->template->$method('task/new', array(
            'ajax' => $this->request->isAjax(),
            'errors' => $errors,
            'values' => $values + array('project_id' => $project['id']),
            'projects_list' => $this->project->getListByStatus(ProjectModel::ACTIVE),
            'columns_list' => $this->board->getColumnsList($project['id']),
            'users_list' => $this->projectPermission->getMemberList($project['id'], true, false, true),
            'colors_list' => $this->color->getList(),
            'categories_list' => $this->category->getList($project['id']),
            'date_format' => $this->config->get('application_date_format'),
            'date_formats' => $this->dateParser->getAvailableFormats(),
            'title' => $project['name'].' &gt; '.t('New task')
        )));
    }

    /**
     * Validate and save a new task
     *
     * @access public
     */
    public function save()
    {
        $project = $this->getProject();
        $values = $this->request->getValues();
        $values['creator_id'] = $this->userSession->getId();

        list($valid, $errors) = $this->taskValidator->validateCreation($values);

        if ($valid) {

            if ($this->taskCreation->create($values)) {
                $this->session->flash(t('Task created successfully.'));

                if (isset($values['another_task']) && $values['another_task'] == 1) {
                    unset($values['title']);
                    unset($values['description']);
                    $this->response->redirect('?controller=task&action=create&'.http_build_query($values));
                }
                else {
                    $this->response->redirect('?controller=board&action=show&project_id='.$project['id']);
                }
            }
            else {
                $this->session->flashError(t('Unable to create your task.'));
            }
        }

        $this->create($values, $errors);
    }

    /**
     * Display a form to edit a task
     *
     * @access public
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();
        $ajax = $this->request->isAjax();

        if (empty($values)) {
            $values = $task;
        }

        $this->dateParser->format($values, array('date_due'));

        $params = array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'users_list' => $this->projectPermission->getMemberList($task['project_id']),
            'colors_list' => $this->color->getList(),
            'categories_list' => $this->category->getList($task['project_id']),
            'date_format' => $this->config->get('application_date_format'),
            'date_formats' => $this->dateParser->getAvailableFormats(),
            'ajax' => $ajax,
        );

        if ($ajax) {
            $this->response->html($this->template->render('task/edit', $params));
        }
        else {
            $this->response->html($this->taskLayout('task/edit', $params));
        }
    }

    /**
     * Validate and update a task
     *
     * @access public
     */
    public function update()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();

        list($valid, $errors) = $this->taskValidator->validateModification($values);

        if ($valid) {

            if ($this->taskModification->update($values)) {
                $this->session->flash(t('Task updated successfully.'));

                if ($this->request->getIntegerParam('ajax')) {
                    $this->response->redirect('?controller=board&action=show&project_id='.$task['project_id']);
                }
                else {
                    $this->response->redirect('?controller=task&action=show&task_id='.$task['id'].'&project_id='.$task['project_id']);
                }
            }
            else {
                $this->session->flashError(t('Unable to update your task.'));
            }
        }

        $this->edit($values, $errors);
    }

    /**
     * Update time tracking information
     *
     * @access public
     */
    public function time()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();

        list($valid,) = $this->taskValidator->validateTimeModification($values);

        if ($valid && $this->taskModification->update($values)) {
            $this->session->flash(t('Task updated successfully.'));
        }
        else {
            $this->session->flashError(t('Unable to update your task.'));
        }

        $this->response->redirect('?controller=task&action=show&task_id='.$task['id'].'&project_id='.$task['project_id']);
    }

    /**
     * Hide a task
     *
     * @access public
     */
    public function close()
    {
        $task = $this->getTask();
        $redirect = $this->request->getStringParam('redirect');

        if ($this->request->getStringParam('confirmation') === 'yes') {

            $this->checkCSRFParam();

            if ($this->taskStatus->close($task['id'])) {
                $this->session->flash(t('Task closed successfully.'));
            } else {
                $this->session->flashError(t('Unable to close this task.'));
            }

            if ($redirect === 'board') {
                $this->response->redirect($this->helper->url('board', 'show', array('project_id' => $task['project_id'])));
            }

            $this->response->redirect($this->helper->url('task', 'show', array('task_id' => $task['id'], 'project_id' => $task['project_id'])));
        }

        if ($this->request->isAjax()) {
            $this->response->html($this->template->render('task/close', array(
                'task' => $task,
                'redirect' => $redirect,
            )));
        }

        $this->response->html($this->taskLayout('task/close', array(
            'task' => $task,
            'redirect' => $redirect,
        )));
    }

    /**
     * Open a task
     *
     * @access public
     */
    public function open()
    {
        $task = $this->getTask();

        if ($this->request->getStringParam('confirmation') === 'yes') {

            $this->checkCSRFParam();

            if ($this->taskStatus->open($task['id'])) {
                $this->session->flash(t('Task opened successfully.'));
            } else {
                $this->session->flashError(t('Unable to open this task.'));
            }

            $this->response->redirect('?controller=task&action=show&task_id='.$task['id'].'&project_id='.$task['project_id']);
        }

        $this->response->html($this->taskLayout('task/open', array(
            'task' => $task,
        )));
    }

    /**
     * Remove a task
     *
     * @access public
     */
    public function remove()
    {
        $task = $this->getTask();

        if (! $this->taskPermission->canRemoveTask($task)) {
            $this->forbidden();
        }

        if ($this->request->getStringParam('confirmation') === 'yes') {

            $this->checkCSRFParam();

            if ($this->task->remove($task['id'])) {
                $this->session->flash(t('Task removed successfully.'));
            } else {
                $this->session->flashError(t('Unable to remove this task.'));
            }

            $this->response->redirect('?controller=board&action=show&project_id='.$task['project_id']);
        }

        $this->response->html($this->taskLayout('task/remove', array(
            'task' => $task,
        )));
    }

    /**
     * Duplicate a task
     *
     * @access public
     */
    public function duplicate()
    {
        $task = $this->getTask();

        if ($this->request->getStringParam('confirmation') === 'yes') {

            $this->checkCSRFParam();
            $task_id = $this->taskDuplication->duplicate($task['id']);

            if ($task_id) {
                $this->session->flash(t('Task created successfully.'));
                $this->response->redirect('?controller=task&action=show&task_id='.$task_id.'&project_id='.$task['project_id']);
            } else {
                $this->session->flashError(t('Unable to create this task.'));
                $this->response->redirect('?controller=task&action=duplicate&task_id='.$task['id'].'&project_id='.$task['project_id']);
            }
        }

        $this->response->html($this->taskLayout('task/duplicate', array(
            'task' => $task,
        )));
    }

    /**
     * Edit description form
     *
     * @access public
     */
    public function description()
    {
        $task = $this->getTask();
        $ajax = $this->request->isAjax() || $this->request->getIntegerParam('ajax');

        if ($this->request->isPost()) {

            $values = $this->request->getValues();

            list($valid, $errors) = $this->taskValidator->validateDescriptionCreation($values);

            if ($valid) {

                if ($this->taskModification->update($values)) {
                    $this->session->flash(t('Task updated successfully.'));
                }
                else {
                    $this->session->flashError(t('Unable to update your task.'));
                }

                if ($ajax) {
                    $this->response->redirect('?controller=board&action=show&project_id='.$task['project_id']);
                }
                else {
                    $this->response->redirect('?controller=task&action=show&task_id='.$task['id'].'&project_id='.$task['project_id']);
                }
            }
        }
        else {
            $values = $task;
            $errors = array();
        }

        $params = array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'ajax' => $ajax,
        );

        if ($ajax) {
            $this->response->html($this->template->render('task/edit_description', $params));
        }
        else {
            $this->response->html($this->taskLayout('task/edit_description', $params));
        }
    }

    /**
     * Move a task to another project
     *
     * @access public
     */
    public function move()
    {
        $task = $this->getTask();
        $values = $task;
        $errors = array();
        $projects_list = $this->projectPermission->getActiveMemberProjects($this->userSession->getId());

        unset($projects_list[$task['project_id']]);

        if ($this->request->isPost()) {

            $values = $this->request->getValues();
            list($valid, $errors) = $this->taskValidator->validateProjectModification($values);

            if ($valid) {

                if ($this->taskDuplication->moveToProject($task['id'], $values['project_id'])) {
                    $this->session->flash(t('Task updated successfully.'));
                    $this->response->redirect('?controller=task&action=show&task_id='.$task['id'].'&project_id='.$values['project_id']);
                }
                else {
                    $this->session->flashError(t('Unable to update your task.'));
                }
            }
        }

        $this->response->html($this->taskLayout('task/move_project', array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'projects_list' => $projects_list,
        )));
    }

    /**
     * Duplicate a task to another project
     *
     * @access public
     */
    public function copy()
    {
        $task = $this->getTask();
        $values = $task;
        $errors = array();
        $projects_list = $this->projectPermission->getActiveMemberProjects($this->userSession->getId());

        unset($projects_list[$task['project_id']]);

        if ($this->request->isPost()) {

            $values = $this->request->getValues();
            list($valid, $errors) = $this->taskValidator->validateProjectModification($values);

            if ($valid) {
                $task_id = $this->taskDuplication->duplicateToProject($task['id'], $values['project_id']);
                if ($task_id) {
                    $this->session->flash(t('Task created successfully.'));
                    $this->response->redirect('?controller=task&action=show&task_id='.$task_id.'&project_id='.$values['project_id']);
                }
                else {
                    $this->session->flashError(t('Unable to create your task.'));
                }
            }
        }

        $this->response->html($this->taskLayout('task/duplicate_project', array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'projects_list' => $projects_list,
        )));
    }

    /**
     * Display the time tracking details
     *
     * @access public
     */
    public function timesheet()
    {
        $task = $this->getTask();

        $subtask_paginator = $this->paginator
            ->setUrl('task', 'timesheet', array('task_id' => $task['id'], 'project_id' => $task['project_id'], 'pagination' => 'subtasks'))
            ->setMax(15)
            ->setOrder('start')
            ->setDirection('DESC')
            ->setQuery($this->subtaskTimeTracking->getTaskQuery($task['id']))
            ->calculateOnlyIf($this->request->getStringParam('pagination') === 'subtasks');

        $this->response->html($this->taskLayout('task/time_tracking', array(
            'task' => $task,
            'subtask_paginator' => $subtask_paginator,
        )));
    }
}

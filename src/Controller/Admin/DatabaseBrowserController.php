<?php

namespace App\Controller\Admin;

use App\Util\AppUtil;
use App\Manager\DatabaseManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class DatabaseBrowserController
 *
 * Database browser controller provides a database tables browser/editor
 *
 * @package App\Controller\Admin
 */
class DatabaseBrowserController extends AbstractController
{
    private AppUtil $appUtil;
    private DatabaseManager $databaseManager;

    public function __construct(AppUtil $appUtil, DatabaseManager $databaseManager)
    {
        $this->appUtil = $appUtil;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Handle database table selector page
     *
     * @return Response The database tables list view
     */
    #[Route('/admin/database', methods: ['GET'], name: 'admin_database_list')]
    public function databaseList(): Response
    {
        // get tables list
        $tables = $this->databaseManager->getTables();

        // return database tables list
        return $this->render('admin/database-browser.twig', [
            'tables' => $tables
        ]);
    }

    /**
     * Handle database table browser page
     *
     * @param Request $request The request object
     *
     * @return Response The database table view
     */
    #[Route('/admin/database/table', methods: ['GET'], name: 'admin_database_browser')]
    public function tableView(Request $request): Response
    {
        // get query parameters
        $table = $this->appUtil->getQueryString('table', $request);
        $page = intval($this->appUtil->getQueryString('page', $request));

        // render table view
        return $this->render('admin/database-browser.twig', [
            // disable not used components
            'tables' => null,
            'editorTable' => null,
            'newRowTable' => null,

            // table browser data
            'tableName' => $table,
            'tableColumns' => $this->databaseManager->getTableColumns($table),
            'tableDataCountAll' => $this->databaseManager->countTableData($table),
            'tableData' => $this->databaseManager->getTableDataByPage($table, $page),
            'tableDataCount' => $this->databaseManager->countTableDataByPage($table, $page),

            // filter properties
            'page' => $page,
            'limit' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE')
        ]);
    }

    /**
     * Handle edit specific database record
     *
     * @param Request $request The request object
     *
     * @return Response The row editor view
     */
    #[Route('/admin/database/edit', methods: ['GET', 'POST'], name: 'admin_database_edit')]
    public function rowEdit(Request $request): Response
    {
        // init error message variable
        $errorMsg = null;

        // get query parameters
        $table = $this->appUtil->getQueryString('table', $request);
        $id = intval($this->appUtil->getQueryString('id', $request));
        $page = intval($this->appUtil->getQueryString('page', $request));

        // get table columns
        $columns = $this->databaseManager->getTableColumns($table);

        // get referer query string
        $referer = $request->query->get('referer');

        // check request is post
        if ($request->isMethod('POST')) {
            // get form submit status
            $formSubmit = $request->request->get('submitEdit');

            // check if user submit edit form
            if (isset($formSubmit)) {
                // update values
                foreach ($columns as $row) {
                    // check if form value is empty
                    if (empty($_POST[$row])) {
                        if ($row != 'id') {
                            $errorMsg = $row . ' is empty';
                            break;
                        }
                    } else {
                        // get value
                        $value = $request->request->get($row);

                        // update value
                        $this->databaseManager->updateValue($table, $row, $value, $id);
                    }
                }

                // redirect back to browser
                if ($errorMsg == null) {
                    return $this->redirectToRoute('admin_database_browser', [
                        'table' => $table,
                        'page' => $page
                    ]);
                }
            }
        }

        // render record editor form view
        return $this->render('admin/database-browser.twig', [
            // disable not used components
            'tables' => null,
            'tableName' => null,
            'newRowTable' => null,

            // row editor data
            'editorId' => $id,
            'editorPage' => $page,
            'errorMsg' => $errorMsg,
            'editorTable' => $table,
            'editorField' => $columns,
            'editorReferer' => $referer,
            'editorValues' => $this->databaseManager->selectRowData($table, $id)
        ]);
    }

    /**
     * Handle add new record to database
     *
     * @param Request $request The request object
     *
     * @return Response The new row form view
     */
    #[Route('/admin/database/add', methods: ['GET', 'POST'], name: 'admin_database_add')]
    public function rowAdd(Request $request): Response
    {
        // init error message variable
        $errorMsg = null;

        // get query parameters
        $table = $this->appUtil->getQueryString('table', $request);
        $page = intval($this->appUtil->getQueryString('page', $request));

        // get table columns
        $columns = $this->databaseManager->getTableColumns($table);

        // check request is post
        if ($request->isMethod('POST')) {
            // get form submit status
            $formSubmit = $request->request->get('submitSave');

            // check is form submited
            if (isset($formSubmit)) {
                $columnsBuilder = [];
                $valuesBuilder = [];

                // build columns and values list
                foreach ($columns as $column) {
                    if ($column != 'id') {
                        $columnValue = $request->request->get($column);
                        if (!empty($columnValue)) {
                            $columnsBuilder[] = $column;
                            $valuesBuilder[] = $columnValue;
                        } else {
                            $errorMsg = 'value: ' . $column . ' is empty';
                            break;
                        }
                    }
                }

                // execute new row insert
                if ($errorMsg == null) {
                    $this->databaseManager->addNew($table, $columnsBuilder, $valuesBuilder);
                }

                // redirect back to browser
                if ($errorMsg == null) {
                    return $this->redirectToRoute('admin_database_browser', [
                        'table' => $table,
                        'page' => $page
                    ]);
                }
            }
        }

        // render new record form view
        return $this->render('admin/database-browser.twig', [
            // disable not used components
            'tables' => null,
            'tableName' => null,
            'editorTable' => null,

            // new row data
            'newRowPage' => $page,
            'errorMsg' => $errorMsg,
            'newRowTable' => $table,
            'newRowColumns' => $columns
        ]);
    }

    /**
     * Handle delete record from database
     *
     * @param Request $request The request object
     *
     * @return Response The redirect to browser
     */
    #[Route('/admin/database/delete', methods: ['GET'], name: 'admin_database_delete')]
    public function rowDelete(Request $request): Response
    {
        // get query parameters
        $id = $this->appUtil->getQueryString('id', $request);
        $table = $this->appUtil->getQueryString('table', $request);
        $page = intval($this->appUtil->getQueryString('page', $request));

        // delete record
        $this->databaseManager->deleteRowFromTable($table, $id);

        // check if record deleted by log-reader
        if ($request->query->get('referer') == 'log_reader') {
            return $this->redirectToRoute('admin_log_list', [
                'page' => $page
            ]);
        }

        // check if record deleted by visitors-manager
        if ($request->query->get('referer') == 'visitor_manager') {
            return $this->redirectToRoute('admin_visitor_manager', [
                'page' => $page
            ]);
        }

        // check if record deleted by media-browser
        if ($request->query->get('referer') == 'media_browser') {
            return $this->redirectToRoute('admin_media_browser', [
                'page' => $page
            ]);
        }

        // check if record deleted by todo-manager
        if ($request->query->get('referer') == 'todo_manager') {
            return $this->redirectToRoute('admin_todos_completed');
        }

        // redirect back to table browser
        return $this->redirectToRoute('admin_database_browser', [
            'table' => $table,
            'page' => $page
        ]);
    }
}

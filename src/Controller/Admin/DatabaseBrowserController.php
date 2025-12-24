<?php

namespace App\Controller\Admin;

use Exception;
use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Manager\DatabaseManager;
use App\Annotation\CsrfProtection;
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
    private ErrorManager $errorManager;
    private DatabaseManager $databaseManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager, DatabaseManager $databaseManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
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
            'tables' => $tables,
            'tableName' => null
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
            'tableColumnsWithTypes' => $this->databaseManager->getTableColumnsWithTypes($table),

            // filter properties
            'page' => $page,
            'limit' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE'),
            'limitValue' => $this->appUtil->getEnvValue('ITEMS_PER_PAGE')
        ]);
    }

    /**
     * Handle edit specific database record
     *
     * @param Request $request The request object
     *
     * @return Response The row editor view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/database/edit', methods: ['GET', 'POST'], name: 'admin_database_edit')]
    public function rowEdit(Request $request): Response
    {
        // init error message variable
        $errorMsg = null;

        // get query parameters
        $table = $this->appUtil->getQueryString('table', $request);
        $id = intval($this->appUtil->getQueryString('id', $request));
        $page = intval($this->appUtil->getQueryString('page', $request));

        // get table columns with types
        $columnsWithTypes = $this->databaseManager->getTableColumnsWithTypes($table);

        // get current row data
        $currentRowData = $this->databaseManager->selectRowData($table, $id);

        // get referer query string
        $referer = $request->query->get('referer');

        // check request is post
        if ($request->isMethod('POST')) {
            // get form submit status
            $formSubmit = $request->request->get('submitEdit');

            // check if user submit edit form
            if (isset($formSubmit)) {
                // update values
                $updatedValues = [];
                foreach ($columnsWithTypes as $columnInfo) {
                    $row = $columnInfo['name'];
                    $type = $columnInfo['type'];
                    $isNullable = $columnInfo['nullable'];

                    // get value from POST data
                    $value = $request->request->get($row);

                    // check if form value is empty (special handling for nullable and TINYINT/BOOLEAN)
                    if ($this->databaseManager->isEmptyValue($value, $type)) {
                        // allow empty values for nullable fields (except id)
                        if (!$isNullable && $row != 'id') {
                            $errorMsg = $row . ' is empty';
                            break;
                        }
                        // for nullable fields, convert empty to null for database
                        $value = null;
                    }

                    // validate data type (only if not null)
                    if ($value !== null && !$this->databaseManager->validateDataType($value, $type)) {
                        $errorMsg = 'Invalid data type for ' . $row . '. Expected: ' . $type;
                        break;
                    }

                    // convert datetime-local format to MySQL DATETIME format
                    $processedValue = $this->databaseManager->convertValueForDatabase($value, $type);

                    // update value
                    try {
                        $this->databaseManager->updateValue($table, $row, $processedValue, $id);
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
                        break;
                    }
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

        // merge current data with submitted data for form repopulation
        if ($errorMsg && !empty($updatedValues)) {
            $displayValues = array_merge($currentRowData, $updatedValues);
        } else {
            $displayValues = $this->databaseManager->selectRowData($table, $id);
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
            'editorReferer' => $referer,
            'editorField' => $columnsWithTypes,
            'editorValues' => $displayValues
        ]);
    }

    /**
     * Handle add new record to database
     *
     * @param Request $request The request object
     *
     * @return Response The new row form view
     */
    #[CsrfProtection(enabled: false)]
    #[Route('/admin/database/add', methods: ['GET', 'POST'], name: 'admin_database_add')]
    public function rowAdd(Request $request): Response
    {
        // init error message variable
        $errorMsg = null;

        // get query parameters
        $table = $this->appUtil->getQueryString('table', $request);
        $page = intval($this->appUtil->getQueryString('page', $request));

        // check if table is specified
        if (empty($table)) {
            $this->errorManager->handleError(
                msg: 'Table parameter is missing for add form',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        // get table columns with types
        try {
            $columnsWithTypes = $this->databaseManager->getTableColumnsWithTypes($table);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                msg: 'Error getting columns for table: ' . $table . ', ' . $e->getMessage(),
                code: Response::HTTP_NOT_FOUND
            );
        }

        $columns = array_column($columnsWithTypes, 'name');

        // init submitted values array - populate from POST if exists
        $submittedValues = [];
        if ($request->isMethod('POST') && !empty($columnsWithTypes)) {
            foreach ($columnsWithTypes as $columnInfo) {
                $column = $columnInfo['name'];
                if ($column != 'id') {
                    $submittedValues[$column] = $request->request->get($column);
                }
            }
        }

        // check request is post
        if ($request->isMethod('POST')) {
            // get form submit status
            $formSubmit = $request->request->get('submitSave');

            // check is form submited
            if (isset($formSubmit)) {
                $columnsBuilder = [];
                $valuesBuilder = [];

                // get all POST data to work with actual submitted values
                $allPostData = $request->request->all();

                // build columns and values list from actual POST data
                foreach ($allPostData as $column => $columnValue) {
                    // skip non-form fields and id
                    if ($column === 'id' || !in_array($column, $columns)) {
                        continue;
                    }

                    // find column type and nullable info
                    $columnType = null;
                    $isNullable = false;
                    foreach ($columnsWithTypes as $columnInfo) {
                        if ($columnInfo['name'] === $column) {
                            $columnType = $columnInfo['type'];
                            $isNullable = $columnInfo['nullable'];
                            break;
                        }
                    }

                    if ($columnType === null) {
                        continue; // skip unknown columns
                    }

                    $submittedValues[$column] = $columnValue; // Store submitted value

                    // check if value is empty (special handling for nullable and boolean fields)
                    if ($this->databaseManager->isEmptyValue($columnValue, $columnType)) {
                        // allow empty values for nullable fields
                        if (!$isNullable) {
                            $errorMsg = 'value: ' . $column . ' is empty';
                            break;
                        }
                        // for nullable fields, convert empty to null for the database
                        $columnValue = null;
                    }

                    // validate data type (only if not null)
                    if ($columnValue !== null && !$this->databaseManager->validateDataType($columnValue, $columnType)) {
                        $errorMsg = 'Invalid data type for ' . $column . '. Expected: ' . $columnType;
                        break;
                    }

                    $columnsBuilder[] = $column;
                    $valuesBuilder[] = $columnValue;
                }

                // execute new row insert
                if ($errorMsg == null) {
                    try {
                        $this->databaseManager->addNew($table, $columnsBuilder, $valuesBuilder);
                    } catch (Exception $e) {
                        $errorMsg = $e->getMessage();
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
            'newRowColumns' => $columnsWithTypes,
            'submittedValues' => $submittedValues
        ]);
    }

    /**
     * Handle delete record from database
     *
     * @param Request $request The request object
     *
     * @return Response The redirect to browser
     */
    #[Route('/admin/database/delete', methods: ['POST'], name: 'admin_database_delete')]
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

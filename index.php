<?php

/**
 * index.php
 * Description:
 *
 */

session_start();

require_once dirname(__FILE__) . '/../../QuantifyMeFramework/includes/includes.php';

$quantifymeAPI = new QuantifyMeAPI();
$quantifymeAPI->validateAPICommon();

if (isset($_REQUEST['noun'])) {
	if ($_REQUEST['noun'] === 'category') {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$quantifymeAPI->validateCreateCategory();
			$category = array(
				'name' => $_REQUEST['name']
			);
			$quantifymeAPI->createCategory($category);
		} else {
			$quantifymeAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'categories') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$quantifymeAPI->getCategories();
		} else {
			$quantifymeAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'action') {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$quantifymeAPI->validateCreateAction();
			$action = array(
				'name' => $_REQUEST['name'],
				'category' => $_REQUEST['category']
			);
			$quantifymeAPI->createAction($action);
		} else {
			$quantifymeAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'actions') {
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$quantifymeAPI->validateGetActions();
			$category = array(
				'category' => $_REQUEST['category']
			);
			$quantifymeAPI->getActions($category);
		} else {
			$quantifymeAPI->badRequest();
		}

	} else if ($_REQUEST['noun'] === 'record') {
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$quantifymeAPI->validateCreateRecord();
			$record = array(
				'user' => $_REQUEST['user'],
				'category' => $_REQUEST['category'],
				'action' => $_REQUEST['action']
			);
			if (isset($_REQUEST['detail'])) $record['detail'] = $_REQUEST['detail'];
			if (isset($_REQUEST['description'])) $record['description'] = $_REQUEST['description'];
			$quantifymeAPI->createRecord($record);
		} else {
			$quantifymeAPI->badRequest();
		}

	} else {
		$quantifymeAPI->resourceNotFound();
	}
} else {
	$quantifymeAPI->resourceNotDefined();
}


class QuantifyMeAPI
{
	private $_uuid;

	private $_timeStamp;
	private $_ip;
	private $_agent;
	private $_language;
	private $_method;

	private $_platform;

	private $_errorCode;
	private $_response;

	private $_queryTime;

	private $_start;
	private $_time;
	private $_packageSize;
	private $_size;
	private $_memoryUsage;

	private $_appVersion;
	private $_osVersion;
	private $_device;
	private $_machine;

	private $_logger;
	private $_mySqlConnect;

	private $_validation;

	public function __construct()
	{
		$this->_start = microtime(true);
		$this->_packageSize = null;
		$this->_response = null;
		$this->_responseType = 'json';

		$this->_queryTime = 0.0;

		$container = new Container();

		$this->_logger = $container->getLogger();

		$this->_mySqlConnect = $container->getMySqlConnect();

		$this->_validation = $container->getValidation();

		$this->beginRequest();
	}

	private function beginRequest()
	{
		$this->logIt('info', '');
		$this->logIt('info', '--------------------------------------------------------------------------------');
		$this->logIt('info', 'API Session Started');

		$this->_uuid = (isset($_REQUEST['uuid'])) ? $_REQUEST['uuid'] : '';

		$this->_timeStamp = (isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : 'NA');
		$this->_ip = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'NA');
		$this->_agent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'NA');
		$this->_language = (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'NA');
		$this->_method = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'NA');

		$this->_platform = 'iOS';
		if (strpos($this->_agent, 'Macintosh') !== FALSE) {
			$this->_platform = 'Mac';
		} else if (strpos($this->_agent, 'Apache') !== FALSE) {
			$this->_platform = 'Android';
		}

		$this->_appVersion = (isset($_REQUEST['appVersion'])) ? $_REQUEST['appVersion'] : '';
		$this->_osVersion = (isset($_REQUEST['osVersion'])) ? $_REQUEST['osVersion'] : '';
		$this->_device = (isset($_REQUEST['device'])) ? $_REQUEST['device'] : '';
		$this->_machine = (isset($_REQUEST['machine'])) ? $_REQUEST['machine'] : '';

		$this->logIt('info', 'TIME: ' . $this->_timeStamp);
		$this->logIt('info', 'IP: ' . $this->_ip);
		$this->logIt('info', 'AGENT: ' . $this->_agent);
		$this->logIt('info', 'LANGUAGE: ' . $this->_language);
		$this->logIt('info', 'VERB: ' . $this->_method);
		$this->logIt('info', 'NOUN: ' . $_REQUEST['noun']);
	}

	public function logIt($level, $message)
	{
		$this->_logger->$level($message);
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// RESPONSE FUNCTIONS /////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function badRequest()
	{
		http_response_code(400);
		$errorCode = 'badRequest';
		$friendlyError = 'Bad Request';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	public function resourceNotFound()
	{
		http_response_code(404);
		$errorCode = 'resourceNotFound';
		$friendlyError = 'Resource Not Found';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	public function resourceNotDefined()
	{
		http_response_code(400);
		$errorCode = 'resourceNotDefined';
		$friendlyError = 'Resource Not Defined';
		$errors = array($friendlyError);
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// VALIDATION FUNCTIONS ///////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function validateAPICommon()
	{
		$this->_validation->validateAPICommon();
		if ($this->_validation->getErrorCount() > 0) {
			$errorCode = $this->_validation->getErrorCode();
			if ($errorCode == 'invalidParameter') {
				http_response_code(400);
				$this->validationFailed();
			} else if ($errorCode == 'missingParameter') {
				http_response_code(404);
				$this->validationFailed();
			}
		}
	}

	public function validateCreateCategory()
	{
		$this->_validation->validateCreateCategory();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	public function validateCreateAction()
	{
		$this->_validation->validateCreateAction();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	public function validateGetActions()
	{
		$this->_validation->validateGetActions();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	public function validateCreateRecord()
	{
		$this->_validation->validateCreateRecord();
		if ($this->_validation->getErrorCount() > 0) {
			$this->validationFailed();
		}
	}

	private function validationFailed()
	{
		http_response_code(400);
		$errorCode = $this->_validation->getErrorCode();
		$errors = $this->_validation->getErrors();
		$friendlyError = $this->_validation->getFriendlyError();
		$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CATEGORY FUNCTIONS /////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function createCategory($category)
	{
		$date = time();
		$categoryObject = new Category($this->_logger, $this->_mySqlConnect->db);
		$categoryObject->setUuid(UUID::getUUID());
		$categoryObject->setCreated($date);
		$categoryObject->setModified($date);
		$categoryObject->setName($category['name']);

		if ($categoryObject->createCategory() === FALSE) {
			http_response_code(500);
			$errorCode = 'categoryNotCreated';
			$friendlyError = 'Category could not be created.';
			$errors = array($friendlyError);
			$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		} else {
			http_response_code(201);
			$this->echoResponse('none', array(), '', 'success', (object)array());
		}
		$this->completeRequest();
	}

	public function getCategories()
	{
		http_response_code(200);
		$categories = Category::GetCategoriesForAPI();
		$response = $categories;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// ACTION FUNCTIONS ///////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function createAction($action)
	{
		$date = time();
		$actionObject = new Action($this->_logger, $this->_mySqlConnect->db);
		$actionObject->setUuid(UUID::getUUID());
		$actionObject->setCreated($date);
		$actionObject->setModified($date);
		$actionObject->setName($action['name']);
		$actionObject->setCategory($action['category']);

		if ($actionObject->createAction() === FALSE) {
			http_response_code(500);
			$errorCode = 'actionNotCreated';
			$friendlyError = 'Action could not be created.';
			$errors = array($friendlyError);
			$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		} else {
			http_response_code(201);
			$this->echoResponse('none', array(), '', 'success', (object)array());
		}
		$this->completeRequest();
	}

	public function getActions($category)
	{
		http_response_code(200);
		$categories = Action::GetActionsForCategoryForAPI($category['category']);
		$response = $categories;
		$this->echoResponse('none', array(), '', 'success', $response);
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// RECORD FUNCTIONS ///////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	public function createRecord($record)
	{
		$date = time();
		$recordObject = new Record($this->_logger, $this->_mySqlConnect->db);
		$recordObject->setCreated($date);
		$recordObject->setModified($date);
		$recordObject->setUser($record['user']);
		$recordObject->setCategory($record['category']);
		$recordObject->setAction($record['action']);
		if (array_key_exists('detail', $record)) $recordObject->setDetail($record['detail']);
		if (array_key_exists('description', $record)) $recordObject->setDescription($record['description']);

		if ($recordObject->createRecord() === FALSE) {
			http_response_code(500);
			$errorCode = 'recordNotCreated';
			$friendlyError = 'Record could not be created.';
			$errors = array($friendlyError);
			$this->echoResponse($errorCode, $errors, $friendlyError, 'fail', (object)array());
		} else {
			http_response_code(201);
			$this->echoResponse('none', array(), '', 'success', (object)array());
		}
		$this->completeRequest();
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////// CLOSING FUNCTIONS //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	private function echoResponse($errorCode, $errors, $friendlyErrors, $result, $data)
	{
		// if a callback is set, assume jsonp and wrap the response in the callback function
		if (isset($_REQUEST['callback']) && strtolower($_REQUEST['responseType'] === 'jsonp')) {
			echo $_REQUEST['callback'] . '(';
		}

		$this->_errorCode = $errorCode;

		$jsonResponse = array();
		$jsonResponse['httpStatus'] = http_response_code();
		$jsonResponse['noun'] = $_REQUEST['noun'];
		$jsonResponse['verb'] = $_SERVER['REQUEST_METHOD'];
		$jsonResponse['errorCode'] = $errorCode;
		$jsonResponse['errors'] = $errors;
		$jsonResponse['friendlyError'] = $friendlyErrors;
		$jsonResponse['result'] = $result;
		$jsonResponse['count'] = count($data);
		$jsonResponse['data'] = $data;
		foreach ($errors as $error) {
			$this->logIt('info', $error);
		}
		$this->_response = json_encode($jsonResponse);
		header('Content-type: application/json');
		echo $this->_response;

		if (isset($_REQUEST['callback']) && strtolower($_REQUEST['responseType'] === 'jsonp')) {
			echo ')';
		}
	}

	private function completeRequest()
	{
		$this->_time = (microtime(true) - $this->_start);
		$this->_packageSize = strlen($this->_response);
		$this->_size = number_format($this->_packageSize);
		$this->_memoryUsage = number_format(memory_get_usage());

		$this->logIt('info', 'Query Time: ' . $this->_queryTime);
		$this->logIt('info', 'Payload Time: ' . $this->_time);
		$this->logIt('info', 'Payload Size: ' . $this->_size);
		$this->logIt('info', 'Memory Usage: ' . $this->_memoryUsage);
		$this->logIt('info', 'API Session Ended');
		$this->logIt('info', '--------------------------------------------------------------------------------');
		$this->logIt('info', '');

		$osVersion = '';
		$osAPILevel = '';
		$carrier = '';
		$device = '';
		$display = '';
		$manufacturer = '';
		$model = '';
		if (isset($_REQUEST['androidInfo'])) {
			$androidInfoArray = explode('|',$_REQUEST['androidInfo']);
			$osVersion = $androidInfoArray[0];
			$osAPILevel = $androidInfoArray[1];
			$carrier = $androidInfoArray[2];
			$device = $androidInfoArray[3];
			$display = $androidInfoArray[4];
			$manufacturer = $androidInfoArray[5];
			$model = $androidInfoArray[6];
		}

		$countForRequest = 0;
		$requestForLogging = '';
		if (isset($_REQUEST['key'])) $_REQUEST['key'] = substr($_REQUEST['key'], 0, 8);
		foreach ($_REQUEST as $key => $value) {
			if ($countForRequest > 0) $requestForLogging .= ' - ';
			$value = urldecode($value);
			$requestForLogging .= $key . ': ' . $value;
			$countForRequest++;
		}
		$this->logIt('debug', ' - REQUESTSTRING: ' . $requestForLogging . ' - ' . $this->_agent);

		$logRequestArguments = array(
			'uuid' => $this->_uuid,
			'noun' => $_REQUEST['noun'],
			'verb' => $_SERVER['REQUEST_METHOD'],
			'request' => $requestForLogging,
			'agent' => $this->_agent,
			'timeStamp' => $this->_timeStamp,
			'language' => $this->_language,
			'httpStatus' => http_response_code(),
			'errorCode' => $this->_errorCode,
			'queryTime' => $this->_queryTime,
			'time' => $this->_time,
			'size' => $this->_size,
			'memory' => $this->_memoryUsage,
			'appVersion' => $this->_appVersion,
			'platform' => $this->_platform,
			'device' => urldecode($this->_device),
			'machine' => $this->_machine,
			'osVersion' => $this->_osVersion,
			'ip' => $this->_ip
		);

		LogRequest::LogRequestToDB($logRequestArguments, $this->_mySqlConnect->db);
		exit();
	}
}

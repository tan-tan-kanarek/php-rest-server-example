<?php

/**
 * RestUserController test case.
 */
class RestUserControllerTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * Constructs the test case.
	 */
	public function curl($service, $action, $data)
	{
		$url = "http://localhost:8089/simple-service-webapp/service/$service";
// 		$url = "http://localhost:8090/service/$service";
		if($action)
			$url .= "/action/$action";
		
		$request = json_encode($data);
		echo "Request: $request\n";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-type: application/json',
		));
		
		$response=curl_exec($ch);
		echo "Response: $response\n";
		curl_close($ch);

		$json = json_decode($response, true);
		
		if($service == 'multirequest')
			return $json;
		
		if(isset($json['result']) && !is_null($json['result']))
			return $json['result'];
		
		if(isset($json['error']))
			throw new Exception($json['error']['code']);
	}
	
	/**
	 * Tests RestUserController->add()
	 */
	private function add()
	{
		$data = array(
			'user' => array(
				'firstName' => uniqid(),
				'lastName' => uniqid(),
			),
		);
		
		$createdUser = $this->curl('user', 'add', $data);

		$this->assertNotNull($createdUser['id']);
		$this->assertNotNull($createdUser['createdAt']);
		$this->assertNotNull($createdUser['updatedAt']);
		$this->assertEquals($data['user']['firstName'], $createdUser['firstName']);
		$this->assertEquals($data['user']['lastName'], $createdUser['lastName']);
		$this->assertArrayHasKey('status', $createdUser);
		$this->assertEquals($createdUser['status'], 0);
		
		return $createdUser;
	}
	
	/**
	 * Tests RestUserController->add()
	 */
	public function testAdd()
	{
		$this->add();
	}
	
	/**
	 * Tests RestUserController->get()
	 */
	public function testGet($id = null)
	{
		if(!$id)
		{
			$createdUser = $this->add();
			$id = $createdUser['id'];
		}
		
		$data = array(
			'id' => $id,
		);
		
		$user = $this->curl('user', 'get', $data);
		
		$this->assertEquals($id, $user['id']);
		
		return $user;
	}
	
	/**
	 * Tests RestUserController->update()
	 */
	public function testUpdate()
	{
		$createdUser = $this->add();
		$id = $createdUser['id'];
		
		$data = array(
			'id' => $id,
			'user' => array(
				'firstName' => uniqid(),
				'lastName' => uniqid(),
			),
		);
		
		sleep(2);
		$user = $this->curl('user', 'update', $data);

		$this->assertEquals($id, $user['id']);
		$this->assertEquals($createdUser['createdAt'], $user['createdAt']);
		$this->assertGreaterThan($createdUser['updatedAt'], $user['updatedAt']);
		$this->assertEquals($data['user']['firstName'], $user['firstName']);
		$this->assertEquals($data['user']['lastName'], $user['lastName']);
		$this->assertArrayHasKey('status', $user);
		$this->assertEquals($user['status'], 0);
		
		return $user;
	}
	
	/**
	 * Tests RestUserController->delete()
	 */
	public function testDelete()
	{
		$createdUser = $this->add();
		$id = $createdUser['id'];
		$data = array(
			'id' => $id,
		);
		
		$this->curl('user', 'delete', $data);
		try
		{
			$user = $this->testGet($id);
			$this->fail("User id [$id] supposed to be deleted");
		}
		catch(Exception $e)
		{
			$this->assertEquals('OBJECT_NOT_FOUND', $e->getMessage());
		}
	}
	
	/**
	 * Tests RestUserController->search()
	 */
	public function testSearch()
	{
		sleep(2);
		$time = time();
		
		$ids = array();
		$count = 5;
		
		for($i = 0; $i < $count; $i++)
		{
			$createdUser = $this->add();
			$ids[] = $createdUser['id'];
		}

		$data = array(
			'filter' => array(
				'createdAtGreaterThanOrEqual' => $time,
			),
		);
		
		sleep(2);
		$usersList = $this->curl('user', 'search', $data);

		$this->assertEquals($count, $usersList['totalCount']);
		foreach($usersList['objects'] as $user)
			$this->assertTrue(in_array($user['id'], $ids));
		
		return $user;
	}
	
	/**
	 * Tests RestUserController->search()
	 */
	public function testPage1()
	{
		sleep(2);
		$time = time();
		
		$ids = array();
		$count = 5;
		$pager = array(
			'pageSize' => 2,
			'pageIndex' => 1,
		);
		
		for($i = 0; $i < $count; $i++)
		{
			$createdUser = $this->add();
			if($i >= ($pager['pageSize'] * ($pager['pageIndex'] - 1)) && $i < ($pager['pageSize'] * $pager['pageIndex']))
				$ids[] = $createdUser['id'];
		}
		$this->assertEquals($pager['pageSize'], count($ids));

		$data = array(
			'filter' => array(
				'createdAtGreaterThanOrEqual' => $time,
			),
			'pager' => $pager,
		);
		
		sleep(2);
		$usersList = $this->curl('user', 'search', $data);

		$this->assertEquals($count, $usersList['totalCount']);
		$this->assertEquals($pager['pageSize'], count($usersList['objects']));
		foreach($usersList['objects'] as $user)
			$this->assertTrue(in_array($user['id'], $ids));
		
		return $user;
	}
	
	/**
	 * Tests RestUserController->search()
	 */
	public function testPage2()
	{
		sleep(2);
		$time = time();
		
		$ids = array();
		$count = 5;
		$pager = array(
			'pageSize' => 2,
			'pageIndex' => 2,
		);
		
		for($i = 0; $i < $count; $i++)
		{
			$createdUser = $this->add();
			if($i >= ($pager['pageSize'] * ($pager['pageIndex'] - 1)) && $i < ($pager['pageSize'] * $pager['pageIndex']))
				$ids[] = $createdUser['id'];
		}
		$this->assertEquals($pager['pageSize'], count($ids));

		$data = array(
			'filter' => array(
				'createdAtGreaterThanOrEqual' => $time,
			),
			'pager' => $pager,
		);
		
		sleep(2);
		$usersList = $this->curl('user', 'search', $data);

		$this->assertEquals($count, $usersList['totalCount']);
		$this->assertEquals($pager['pageSize'], count($usersList['objects']));
		foreach($usersList['objects'] as $user)
			$this->assertTrue(in_array($user['id'], $ids));
		
		return $user;
	}

	public function testMultiRequest()
	{
		$data = array(
			array(
				'service' => 'user',
				'action' => 'add',
				'user' => array(
					'firstName' => uniqid(),
					'lastName' => uniqid(),
				),
			),
			array(
				'service' => 'user',
				'action' => 'get',
				'id' => '{results:1:id}',
			),
			array(
				'service' => 'user',
				'action' => 'update',
				'id' => '{results:1:id}',
				'user' => array(
					'firstName' => uniqid(),
					'lastName' => uniqid(),
				),
			),
			array(
				'service' => 'user',
				'action' => 'delete',
				'id' => '{results:1:id}',
			),
			array(
				'service' => 'user',
				'action' => 'get',
				'id' => '{results:1:id}',
			),
		);

		$response = $this->curl('multirequest', null, $data);

		$this->assertArrayHasKey('result', $response);
		$responses = $response['result'];
		$this->assertEquals(count($data), count($responses));

		// add
		$this->assertNotNull($responses[0]['result']['id']);
		$id = $responses[0]['result']['id'];
		$this->assertNotNull($responses[0]['result']['createdAt']);
		$this->assertNotNull($responses[0]['result']['updatedAt']);
		$this->assertEquals($data[0]['user']['firstName'], $responses[0]['result']['firstName']);
		$this->assertEquals($data[0]['user']['lastName'], $responses[0]['result']['lastName']);
		$this->assertArrayHasKey('status', $responses[0]['result']);
		$this->assertEquals($responses[0]['result']['status'], 0);

		// get
		$this->assertEquals($id, $responses[1]['result']['id']);
		
		// update
		$this->assertEquals($id, $responses[2]['result']['id']);
		$this->assertEquals($responses[0]['result']['createdAt'], $responses[2]['result']['createdAt']);
		$this->assertGreaterThanOrEqual($responses[0]['result']['updatedAt'], $responses[2]['result']['updatedAt']);
		$this->assertEquals($data[2]['user']['firstName'], $responses[2]['result']['firstName']);
		$this->assertEquals($data[2]['user']['lastName'], $responses[2]['result']['lastName']);
		$this->assertArrayHasKey('status', $responses[2]['result']);
		$this->assertEquals($responses[2]['result']['status'], 0);
		
		// delete
		$this->assertTrue(!isset($responses[3]['result']) || is_null($responses[3]['result']));
		$this->assertFalse(isset($responses[3]['error']));
		
		// invalid get
		$this->assertTrue(!isset($responses[4]['result']) || is_null($responses[4]['result']));
		$this->assertTrue(isset($responses[4]['error']));
		$this->assertEquals('OBJECT_NOT_FOUND', $responses[4]['error']['code']);
	}
}


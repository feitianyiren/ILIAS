<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/TermsOfService/classes/class.ilTermsOfServiceDataGatewayFactory.php';

/**
 * @author  Michael Jansen <mjansen@databay.de>
 * @version $Id$
 */
class ilTermsOfServiceDataGatewayFactoryTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var bool
	 */
	protected $backupGlobals = false;

	/**
	 *
	 */
	public function setUp()
	{
		if(!defined('MDB2_AUTOQUERY_INSERT'))
		{
			define('MDB2_AUTOQUERY_INSERT', 1);
		}

		parent::setUp();
	}

	/**
	 *
	 */
	public function testInstanceCanBeCreated()
	{
		$factory = new ilTermsOfServiceDataGatewayFactory();
		$this->assertInstanceOf('ilTermsOfServiceDataGatewayFactory', $factory);
	}

	/**
	 * @expectedException ilTermsOfServiceMissingDatabaseAdapterException
	 */
	public function testExceptionIsRaisedWhenWhenGatewayIsRequestedWithMissingDependencies()
	{
		$factory = new ilTermsOfServiceDataGatewayFactory();
		$factory->getByName('PHP Unit');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionIsRaisedWhenUnknowDataGatewayIsRequested()
	{
		$factory = new ilTermsOfServiceDataGatewayFactory();
		$factory->setDatabaseAdapter($this->getMockBuilder('ilDBInterface')->getMock());
		$factory->getByName('PHP Unit');
	}

	/**
	 *
	 */
	public function testAcceptanceDatabaseGatewayIsReturnedWhenRequestedByName()
	{
		$factory = new ilTermsOfServiceDataGatewayFactory();
		$factory->setDatabaseAdapter($this->getMockBuilder('ilDBInterface')->getMock());
		$this->assertInstanceOf('ilTermsOfServiceAcceptanceDatabaseGateway', $factory->getByName('ilTermsOfServiceAcceptanceDatabaseGateway'));
	}

	/**
	 *
	 */
	public function testFactoryShouldReturnDatabaseAdapterWhenDatabaseAdapterIsSet()
	{
		$expected = $this->getMockBuilder('ilDBInterface')->getMock();

		$factory = new ilTermsOfServiceDataGatewayFactory();
		$factory->setDatabaseAdapter($expected);
		$this->assertEquals($expected, $factory->getDatabaseAdapter());
	}
}
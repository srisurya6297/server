<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\Tests\AppInfo;

use OC\Settings\Manager;
use OC\Settings\Section;
use OCA\Settings\Admin\Sharing;
use OCA\Settings\Personal\Security;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Settings\ISubAdminSettings;
use Test\TestCase;

class ManagerTest extends TestCase {

	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	private $manager;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nFactory;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $url;
	/** @var IServerContainer|\PHPUnit_Framework_MockObject_MockObject */
	private $container;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->container = $this->createMock(IServerContainer::class);

		$this->manager = new Manager(
			$this->logger,
			$this->l10nFactory,
			$this->url,
			$this->container
		);
	}

	public function testGetAdminSections() {
		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);

		$this->assertEquals([
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getAdminSections());
	}

	public function testGetPersonalSections() {
		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);

		$this->assertEquals([
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getPersonalSections());
	}

	public function testGetAdminSectionsEmptySection() {
		$this->assertEquals([], $this->manager->getAdminSections());
	}

	public function testGetPersonalSectionsEmptySection() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->assertEquals([], $this->manager->getPersonalSections());
	}

	public function testGetAdminSettings() {
		$section = $this->createMock(Sharing::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(13);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing');

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetAdminSettingsAsSubAdmin() {
		$section = $this->createMock(Sharing::class);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([], $settings);
	}

	public function testGetSubAdminSettingsAsSubAdmin() {
		$section = $this->createMock(ISubAdminSettings::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(13);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetPersonalSettings() {
		$section = $this->createMock(Security::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(16);
		$section2 = $this->createMock(Security\Authtokens::class);
		$section2->expects($this->once())
			->method('getPriority')
			->willReturn(100);
		$this->container->expects($this->at(0))
			->method('query')
			->with(Security::class)
			->willReturn($section);
		$this->container->expects($this->at(1))
			->method('query')
			->with(Security\Authtokens::class)
			->willReturn($section2);

		$settings = $this->manager->getPersonalSettings('security');

		$this->assertEquals([
			16 => [$section],
			100 => [$section2],
		], $settings);
	}

	public function testSameSectionAsPersonalAndAdmin() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);
		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);

		$this->assertEquals([
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getPersonalSections());

		$this->assertEquals([
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getAdminSections());
	}
}

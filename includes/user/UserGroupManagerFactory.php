<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\User;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWiki\Permissions\GroupPermissionsLookup;
use MediaWiki\User\TempUser\TempUserConfig;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ConfiguredReadOnlyMode;
use Wikimedia\Rdbms\ILBFactory;

/**
 * Factory service for UserGroupManager instances. This allows UserGroupManager to be created for
 * cross-wiki access.
 *
 * @since 1.35
 */
class UserGroupManagerFactory {
	/** @var ServiceOptions */
	private $options;

	/** @var ConfiguredReadOnlyMode */
	private $configuredReadOnlyMode;

	/** @var ILBFactory */
	private $dbLoadBalancerFactory;

	/** @var UserEditTracker */
	private $userEditTracker;

	/** @var GroupPermissionsLookup */
	private $groupPermissionLookup;

	/** @var JobQueueGroupFactory */
	private $jobQueueGroupFactory;

	/** @var LoggerInterface */
	private $logger;

	/** @var callable[] */
	private $clearCacheCallbacks;

	/** @var HookContainer */
	private $hookContainer;

	/** @var TempUserConfig */
	private $tempUserConfig;

	/**
	 * @param ServiceOptions $options
	 * @param ConfiguredReadOnlyMode $configuredReadOnlyMode
	 * @param ILBFactory $dbLoadBalancerFactory
	 * @param HookContainer $hookContainer
	 * @param UserEditTracker $userEditTracker
	 * @param GroupPermissionsLookup $groupPermissionsLookup
	 * @param JobQueueGroupFactory $jobQueueGroupFactory
	 * @param LoggerInterface $logger
	 * @param TempUserConfig $tempUserConfig Assumed to be the same across all domains.
	 * @param callable[] $clearCacheCallbacks
	 */
	public function __construct(
		ServiceOptions $options,
		ConfiguredReadOnlyMode $configuredReadOnlyMode,
		ILBFactory $dbLoadBalancerFactory,
		HookContainer $hookContainer,
		UserEditTracker $userEditTracker,
		GroupPermissionsLookup $groupPermissionsLookup,
		JobQueueGroupFactory $jobQueueGroupFactory,
		LoggerInterface $logger,
		TempUserConfig $tempUserConfig,
		array $clearCacheCallbacks = []
	) {
		$this->options = $options;
		$this->configuredReadOnlyMode = $configuredReadOnlyMode;
		$this->dbLoadBalancerFactory = $dbLoadBalancerFactory;
		$this->hookContainer = $hookContainer;
		$this->userEditTracker = $userEditTracker;
		$this->groupPermissionLookup = $groupPermissionsLookup;
		$this->jobQueueGroupFactory = $jobQueueGroupFactory;
		$this->logger = $logger;
		$this->tempUserConfig = $tempUserConfig;
		$this->clearCacheCallbacks = $clearCacheCallbacks;
	}

	/**
	 * @param string|false $dbDomain
	 * @return UserGroupManager
	 */
	public function getUserGroupManager( $dbDomain = false ): UserGroupManager {
		// TODO: Once UserRightsProxy is removed, cache the instance per domain.
		return new UserGroupManager(
			$this->options,
			$this->configuredReadOnlyMode,
			$this->dbLoadBalancerFactory,
			$this->hookContainer,
			$this->userEditTracker,
			$this->groupPermissionLookup,
			$this->jobQueueGroupFactory->makeJobQueueGroup( $dbDomain ),
			$this->logger,
			$this->tempUserConfig,
			$this->clearCacheCallbacks,
			$dbDomain
		);
	}
}

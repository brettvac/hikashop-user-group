<?php
/**
 * @package	HikaShop for Joomla!
 * @subpackage  User Group After Purchase Plugin
 * @version	5.0.0
 * @author	hikashop.com (original), ChatGPT (with some help from Google)
 * @copyright	(C) 2010-2014 HIKARI SOFTWARE. Modifications 2025 for Hikashop 5.x.x
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;

?><?php
class plgHikashopGroup extends JPlugin {
  public function plgHikashopGroup(&$subject, $config) {
    parent::__construct($subject, $config);
  }

  public function onProductDisplay(&$element, &$html) {
    ob_start();
?>
    <div class="hkc-xl-4 hkc-lg-6 hikashop_product_block hikashop_product_edit_meta">
        <div class="hikashop_product_part_title">
            <?php echo JText::_('USER_GROUP_AFTER_PURCHASE'); ?>
        </div>
        <dl class="hika_options">
            <dt>
                <label for="product_group_after_purchase"><?php echo JText::_('GROUP_NAME'); ?></label>
            </dt>
            <dd>
                <?php
                $subscriptiontype = hikashop_get('type.subscription');
                echo $subscriptiontype->display('product_group_after_purchase', $element->product_group_after_purchase, 'product');
                ?>
            </dd>
        </dl>
    </div>
<?php
    $html[] = ob_get_clean();
  }

  public function onAfterOrderCreate(&$order, &$send_email) {
    return $this->onAfterOrderUpdate($order, $send_email);
  }

  public function onAfterOrderUpdate(&$order, &$send_email) {
    $config = & hikashop_config();
    $confirmed = $config->get('order_confirmed_status');
    if (!isset($order->order_status)) return true;

    $mainframe = JFactory::getApplication();
    $db = JFactory::getDBO();

    $class = hikashop_get('class.order');
    $dbOrder = $class->get($order->order_id);
    $class = hikashop_get('class.user');
    $data = $class->get($dbOrder->order_user_id);

    if (empty($data->user_cms_id)) {
      if (JFactory::getApplication()
        ->isClient('administrator')) {
        $mainframe->enqueueMessage('The customer ' . $dbOrder->order_user_id . 'does not have a joomla user account so his group cannot be changed', 'notice');
      }
      return true;
    }

    $db->setQuery('SELECT b.*,a.* FROM `#__hikashop_order_product` as a LEFT JOIN `#__hikashop_product` as b ON a.product_id=b.product_id WHERE a.order_id = ' . (int)$dbOrder->order_id . ' AND b.product_group_after_purchase!=\'\'');
    $allProducts = $db->loadObjectList();

    if (empty($allProducts)) {
      return true;
    }

    if ($order->order_status != $confirmed) {
      return true;
    }

    if (!version_compare(JVERSION, '1.6.0', '<')) {
      jimport('joomla.access.access');
      $userGroups = JAccess::getGroupsByUser($data->user_cms_id, true);
    }
    $user = clone (JFactory::getUser($data->user_cms_id));

    $no_change = true;
    foreach ($allProducts as $oneProduct) {
      if (hikashop_isAllowed($oneProduct->product_group_after_purchase, $data->user_cms_id)) {
        continue;
      }
      $no_change = false;

      if (!version_compare(JVERSION, '1.6.0', '<')) {
        $userGroups[] = $oneProduct->product_group_after_purchase;
      }
      else {
        if ($user->gid != 25) {
          $user->set('gid', $oneProduct->product_group_after_purchase);
          $acl = JFactory::getACL();
          $user->set('usertype', $acl->get_group_name($oneProduct->product_group_after_purchase));
        }
      }

      if (JFactory::getApplication()
        ->isClient('administrator')) {
        $mainframe->enqueueMessage('The user ' . $dbOrder->order_user_id . ' is now in the group ' . $oneProduct->product_group_after_purchase);
      }
    }
    if (!version_compare(JVERSION, '1.6.0', '<') && !$no_change) {
      $user->set('groups', $userGroups);
      $user->save();
    }

    if ($no_change) {
      if (JFactory::getApplication()->isClient('administrator')) {
        $mainframe->enqueueMessage('The customer of that order is already in the good user group', 'notice');
      }
      return true;
    }
    else {
      $pluginsClass = hikashop_get('class.plugins');
      $plugin = $pluginsClass->getByName('hikashop', 'group');
      $force_logout = $this
        ->params
        ->get('force_logout');
      if (empty($force_logout)) {
        return true;
      }
      $conf = JFactory::getConfig();
      $handler = $conf->get('session_handler', 'none');
      if ($handler == 'database') {
        $db->setQuery('DELETE FROM ' . hikashop_table('session', false) . ' WHERE client_id=0 AND userid = ' . (int)$data->user_cms_id);
        $db->getQuery(true);
      }
      if (!JFactory::getApplication()
        ->isClient('administrator')) {
        $mainframe->logout($data->user_cms_id);
      }
    }
  }

  public function _updateGroup($user_id, $new_group_id, $remove_group_id = 0) {
    $container = Factory::getContainer();
    $userFactory = $container->get(UserFactoryInterface::class);
    $user = $userFactory->loadUserById($user_id);

    if (!$user || !$user->id) {
      return false; // User not found
    }

    $userGroups = $user->groups;

    // Add new group if not already present
    if (!in_array($new_group_id, $userGroups)) {
      $userGroups[] = $new_group_id;
    }

    // Remove old group if needed
    if (!empty($remove_group_id)) {
      $key = array_search($remove_group_id, $userGroups);
      if ($key !== false) {
        unset($userGroups[$key]);
      }
    }
    $user->groups = $userGroups; // Update user groups
    return $user->save(true);
  }

}
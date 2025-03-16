# Hikashop Group Plugin

This plugin enables you to change the group of a user after purchasing a product in Hikashop. Works with Joomla! 4x and 5x and Hikashop 5.x

## Description
The Hikashop Group Plugin allows you to automatically change a user's group in Joomla after they purchase a specific product in Hikashop. This is useful for managing user permissions and access based on their purchases.

## Features
- Changes user group after product purchase
- Configurable option to force user logout on group update
- Works with Joomla session handler set to database mode

## Configuration Options
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| force_logout | Radio | 1 (Yes) | Force user logout on group update (only with Joomla session handler option set to use the database) |
| Options: | | | - 0: No |
|          | | | - 1: Yes |

## Installation
1. Download the plugin from: [https://github.com/brettvac/hikashop-user-group/archive/refs/heads/main.zip](https://github.com/brettvac/hikashop-user-group/archive/refs/heads/main.zip)
2. Install via Joomla's extension manager
3. Configure the plugin parameters in Hikashop

## Files
- `group.php` - Main plugin file

## Source
The code for this plugin originates from this forum post:  
[https://www.hikashop.com/forum/orders-management/866710-user-group-after-purchase-with-multiple-purchase.html#148692](https://www.hikashop.com/forum/orders-management/866710-user-group-after-purchase-with-multiple-purchase.html#148692)

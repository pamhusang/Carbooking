<?php
/**
 * @filesource modules/index/models/telegram.php
 *
 * @copyright 2025 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Index\Telegram;

use Kotchasan\Http\Request;

/**
 * module=telegram
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ทดสอบการส่ง TelegramBot
     *
     * @param Request $request
     */
    public function test(Request $request)
    {
        // referer
        if ($request->isReferer() && $request->isAjax()) {
            // รับค่าจากฟอร์ม
            $bot_token = $request->post('bot_token')->topic();
            $chat_id = $request->post('chat_id')->topic();

            // ทดสอบส่งข้อความ TelegramBot
            echo \Gcms\TelegramBot::sendTo($chat_id, strip_tags(self::$cfg->web_title), $bot_token);
        }
    }
}

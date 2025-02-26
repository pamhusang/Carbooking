<?php
/**
 * @filesource Gcms/TelegramBot.php
 *
 * @copyright 2025 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

/**
 *  Telegram API Class
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class TelegramBot extends \Kotchasan\KBase
{
    /**
     * @var mixed
     */
    private static $apiUrl = "https://api.telegram.org/bot";

    /**
     * ฟังก์ชันสำหรับส่งคำขอไปยัง Telegram API
     *
     * @param string $method ชื่อเมธอดของ Telegram API
     * @param array $params พารามิเตอร์สำหรับส่งไปกับ API
     * @param string|null $botToken โทเค็นของบอท (ถ้าไม่ระบุจะใช้ค่าเริ่มต้นจากการตั้งค่า)
     *
     * @return array|false ผลลัพธ์จาก API หรือ false หากมีข้อผิดพลาด
     */
    private static function sendRequest($method, $params = [], $botToken = null)
    {
        if ($botToken == null) {
            $botToken = self::$cfg->telegram_bot_token;
        }
        if (empty($botToken)) {
            return 'API key can not be empty';
        }

        $url = self::$apiUrl.$botToken."/".$method;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params)
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        return json_decode($response, true);
    }

    /**
     * ฟังก์ชันสำหรับส่งข้อความ
     *
     * @param int $chatId ID ของแชทหรือผู้รับ
     * @param string $text ข้อความที่ต้องการส่ง
     * @param string|null $botToken โทเค็นของบอท (ถ้าไม่ระบุจะใช้ค่าเริ่มต้นจากการตั้งค่า)
     *
     * @return array|false ผลลัพธ์จาก API หรือ false หากมีข้อผิดพลาด
     */
    public static function sendTo($chatId, $text, $botToken = null)
    {
        $ret = self::sendRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => self::toText($text)
        ], $botToken);

        if (isset($ret['error_code']) && isset($ret['description'])) {
            return $ret['description'];
        }
        return '';
    }

    /**
     * ฟังก์ชันสำหรับส่งข้อความ Notify
     *
     * @param string $text ข้อความที่ต้องการส่ง
     *
     * @return array|false ผลลัพธ์จาก API หรือ false หากมีข้อผิดพลาด
     */
    public static function send($text)
    {
        return self::sendTo(self::$cfg->telegram_chat_id, $text);
    }

    /**
     * ฟังก์ชันสำหรับตั้งค่า Webhook
     * @param string $url URL ของ Webhook ที่ต้องการตั้งค่า
     * @return array|false ผลลัพธ์จาก API หรือ false หากมีข้อผิดพลาด
     */
    public function setWebhook($url)
    {
        return self::sendRequest('setWebhook', [
            'url' => $url
        ]);
    }

    /**
     * ฟังก์ชันสำหรับลบ Webhook
     * @return array|false ผลลัพธ์จาก API หรือ false หากมีข้อผิดพลาด
     */
    public function deleteWebhook()
    {
        return self::sendRequest('deleteWebhook');
    }

    /**
     * คืนค่าข้อความ ตัด tag
     * ลบข้อความนอก td, th เพื่อรักษาแถวของตารางไว้
     * แปลง <br> เป็น \n
     *
     * @param string $message
     *
     * @return string
     */
    private static function toText($message)
    {
        // ใช้ preg_replace_callback เพื่อจับคู่เฉพาะ <tr> แล้วลบช่องว่างที่ไม่อยู่ใน <td> และ <th>
        $message = preg_replace_callback(
            '/<tr\b[^>]*>(.*?)<\/tr>/s',
            function ($matches) {
                // ดึงเนื้อหาภายใน <tr>
                $trContent = $matches[1];

                // ใช้ preg_replace_callback เพื่อจับคู่ <td> และ <th>
                $cleanedTrContent = preg_replace_callback(
                    '/<\/?(td|th)\b[^>]*>(.*?)<\/\2>/s',
                    function ($cellMatches) {
                        // เก็บเนื้อหาของ <td> และ <th> ไว้
                        return '<td>'.$cellMatches[1].'</td>';
                    },
                    $trContent
                );

                // ลบช่องว่างนอก <td> และ <th>
                $cleanedTrContent = preg_replace('/\n+/', '', $cleanedTrContent);

                // คืนค่า <tr> ที่ถูกแก้ไขแล้ว
                return '<tr>'.$cleanedTrContent.'</tr>';
            },
            str_replace(["\r", "\t"], '', $message)
        );
        // แปลง <br> เป็น \n สำหรับขึ้นบรรทัดใหม่
        $message = str_replace(['<br>', '<br />'], "\n", $message);
        // ข้อความ ตัด tag
        $msg = [];
        foreach (explode("\n", strip_tags($message)) as $row) {
            $row = trim($row);
            if ($row != '') {
                $msg[] = $row;
            }
        }
        return \Kotchasan\Text::unhtmlspecialchars(implode("\n", $msg));
    }
}

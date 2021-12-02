<?php
/*
 * Handler of Telegram callbacks
 */

require '../service/requireall.php';

$content = file_get_contents("php://input");
$update = json_decode($content);

if (!$update) {
    // received wrong update, must not happen
    exit;
}

if (isset($update->message)) {
    // new message
    $msg = $update->message;
    $msg = new Message($msg);
    $peer = $msg->getPeer();
    $uid = $msg->getUserID();
    $text = $msg->getText();
    $uptext = $msg->getUptext();
    $contact = $msg->getContact();
    $image = $msg->getImage();

    // БЛОК РЕГИСТРАЦИИ
    try {
        $user = new User($uid);
    } catch (UserNotFoundException $e) {
        if (!is_null($contact)) {
            if ($contact->user_id != $uid) {
                API::sendMessage($peer, "Произошла ошибка! Повторите операцию!");
                exit;
            }
            $phone = $contact->phone_number;
            try {
                $user = new User($uid, true, $phone);
            } catch (Exception $e) {
                API::sendMessage($peer, "Вы не зарегистрированы в системе!");
                exit;
            }
            API::sendKeyboardedMessage($peer, "Здравствуйте! Это чат-бот Промсвязьбанка для сотрудников. Высылаю Вам логин и пароль для доступа в личный кабинет:\n\nЛогин: {$phone}\nПароль: пароль\n\nРады видеть Вас в компании!", json_encode(array("remove_keyboard" => true), JSON_UNESCAPED_UNICODE));
        } else {
            $keyboard = array(
                "resize_keyboard" => true,
                "one_time_keyboard" => true,
                "keyboard" => array(
                    array(
                        array(
                            "text" => "Отправить номер телефона",
                            "request_contact" => true
                        )
                    )
                )
            );
            $kb = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
            API::sendKeyboardedMessage($peer, "Для использования бота войдите с помощью номера телефона.", $kb);
            exit;
        }
    }
}

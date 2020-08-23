<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class DumpTelegram extends Controller
{
    public function Dump()
    {
        if (!file_exists('madeline.php')) {
            copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
        }
        include 'madeline.php';

        ///TODO: Hide auth data to conf/env

        /// 1. INPUT ACCOUNT API ID, HASH
        $settings['app_info']['api_id'] = 0000000;
        $settings['app_info']['api_hash'] = '';

        $MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);

        ///TODO: Improve auth (by web forms/transfer sessions)

        /// 2. COMMENT THIS 2 LINES BEFORE FIRST START
        /// 9. AFTER LOGIN UNCOMMENT AGAIN
        $MadelineProto->start();
        $account["Authorization"] = $MadelineProto->account->getAuthorizations();

        $MadelineProto->async(true);

        $MadelineProto->loop(function () use ($MadelineProto) {

            /// 3. UNCOMMENT THIS 2 LINES BEFORE FIRST START
            /// 4. INPUT ACC PHONE
            /// 5. GOTO localhost/dump_news
            /// 6. AFTER ERROR INPUT CODE FROM TELEGRAM
            /// 7. GOTO localhost/dump_news
            /// 8. AFTER LOGIN COMMENT AGAIN
            /*
            yield $MadelineProto->phoneLogin('+79990000000');
            $authorization = yield $MadelineProto->completePhoneLogin('00000');
            */

            foreach (yield $MadelineProto->getDialogs() as $peer)
            {
                if (array_key_exists('_', $peer))
                {
                    if ($peer['_'] == "peerChannel" and array_key_exists('channel_id', $peer))
                    {
                        $peerExist = DB::table('channels')->where('id', $peer['channel_id'])->first();
                        if (is_null($peerExist))
                        {
                            $messages = yield $MadelineProto->messages->getHistory([
                                'peer' => $peer,
                                'offset_id' => 0,
                                'offset_date' => 0,
                                'add_offset' => 0,
                                'limit' => 1,
                                'max_id' => 0,
                                'min_id' => 0,
                                'hash' => 0
                            ]);

                            if (count($messages['messages']) == 0)
                            {
                                DB::table('channels')->insert(
                                    [
                                        'id' => $peer['channel_id'],
                                        'link' => "___",
                                        'name' => "___",
                                        'lastMessageID' => 0
                                    ]
                                );
                            }
                            else
                            {
                                $message = $messages['messages'][0]['id'];

                                DB::table('channels')->insert(
                                    [
                                        'id' => $peer['channel_id'],
                                        'link' => "___",
                                        'name' => "___",
                                        'lastMessageID' => $message
                                    ]
                                );

                                $offset = 0;

                                $messages = yield $MadelineProto->messages->getHistory([
                                    'peer' => $peer,
                                    'offset_id' => 0,
                                    'offset_date' => 0,
                                    'add_offset' => $offset,
                                    'limit' => 100,
                                    'max_id' => 0,
                                    'min_id' => 0,
                                    'hash' => 0
                                ]);

                                foreach ($messages["messages"] as $message)
                                {
                                    if (array_key_exists('message', $message))
                                    {
                                        if (!empty($message['message']))
                                        {
                                            try {
                                                DB::table('messages')->insert(
                                                    [
                                                        'channelID' => $peer['channel_id'],
                                                        'id' => $message['id'],
                                                        'text' => $message['message']
                                                    ]
                                                );
                                            }
                                            catch (Exception $e) {}
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                            $offsetMinId = $peerExist["lastMessageID"];

                            $offset = 0;

                            while (true)
                            {
                                $messages = yield $MadelineProto->messages->getHistory([
                                    'peer' => $peer,
                                    'offset_id' => 0,
                                    'offset_date' => 0,
                                    'add_offset' => $offset,
                                    'limit' => 100,
                                    'max_id' => 0,
                                    'min_id' => $offsetMinId,
                                    'hash' => 0
                                ]);

                                foreach ($messages["messages"] as $message)
                                {
                                    if (array_key_exists('message', $message))
                                    {
                                        if (!empty($message['message']))
                                        {
                                            try {
                                                DB::table('messages')->insert(
                                                    [
                                                        'channelID' => $peer['channel_id'],
                                                        'id' => $message['id'],
                                                        'text' => $message['message']
                                                    ]
                                                );
                                            }
                                            catch (Exception $e)
                                            {

                                            }
                                        }
                                    }
                                }

                                if (count($messages) > 0)
                                {
                                    $offset += count($messages);
                                }
                                else
                                {
                                    break;
                                }
                            }

                            $messages = yield $MadelineProto->messages->getHistory([
                                'peer' => $peer,
                                'offset_id' => 0,
                                'offset_date' => 0,
                                'add_offset' => 0,
                                'limit' => 1,
                                'max_id' => 0,
                                'min_id' => 0,
                                'hash' => 0
                            ]);

                            $lastMessageName = $messages['messages'][0]['id'];

                            DB::table('channels')
                                ->where('id', $peer['channel_id'])
                                ->update(['lastMessageID' => $lastMessageName]);
                        }
                    }
                }
            }
        });
    }
}

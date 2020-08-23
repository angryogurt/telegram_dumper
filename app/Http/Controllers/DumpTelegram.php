<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $settings['app_info']['api_id'] = 1480353;
        $settings['app_info']['api_hash'] = '71b9dd00160eaf508939711d09b0fbed';

        $MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);

        $MadelineProto->start();

        $account["Authorization"] = $MadelineProto->account->getAuthorizations();

        $MadelineProto->async(true);

        $MadelineProto->loop(function () use ($MadelineProto) {

            /*
            yield $MadelineProto->phoneLogin('+79875066080');
            $authorization = yield $MadelineProto->completePhoneLogin('54218');
            if ($authorization['_'] === 'account.password') {
                $authorization = yield $MadelineProto->complete2falogin(yield $MadelineProto->readline('Please enter your password (hint '.$authorization['hint'].'): '));
            }
            */

            foreach (yield $MadelineProto->getDialogs() as $peer)
            {
                if (array_key_exists('_', $peer))
                {
                    if ($peer['_'] == "peerChannel")
                    {
                        if (array_key_exists('channel_id', $peer))
                        {
                            $peerExist = DB::table('channels')->where('id', $peer['channel_id'])->first();
                            if (is_null($peerExist)) // если записи о канале нет в бд
                            {
                                $msgs = yield $MadelineProto->messages->getHistory([
                                    'peer' => $peer,
                                    'offset_id' => 0,
                                    'offset_date' => 0,
                                    'add_offset' => 0,
                                    'limit' => 1,
                                    'max_id' => 0,
                                    'min_id' => 0,
                                    'hash' => 0
                                ]);

                                if (count($msgs['messages']) == 0) //если в канале нет сообщений
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
                                else //если в канале есть сообщения
                                {
                                    $msg = $msgs['messages'][0]['id'];

                                    DB::table('channels')->insert(
                                        [
                                            'id' => $peer['channel_id'],
                                            'link' => "___",
                                            'name' => "___",
                                            'lastMessageID' => $msg
                                        ]
                                    );

                                    $offset = 0;

                                    $msgs = yield $MadelineProto->messages->getHistory([
                                        'peer' => $peer,
                                        'offset_id' => 0,
                                        'offset_date' => 0,
                                        'add_offset' => $offset,
                                        'limit' => 100,
                                        'max_id' => 0,
                                        'min_id' => 0,
                                        'hash' => 0
                                    ]);

                                    foreach ($msgs["messages"] as $msg)
                                    {
                                        if (array_key_exists('message', $msg))
                                        {
                                            if (!empty($msg['message']))
                                            {
                                                try {
                                                    DB::table('messages')->insert(
                                                        [
                                                            'channelID' => $peer['channel_id'],
                                                            'id' => $msg['id'],
                                                            'text' => $msg['message']
                                                        ]
                                                    );
                                                }
                                                catch (Exception $e)
                                                {

                                                }
                                            }
                                        };
                                    };
                                }
                            }
                            else //если запись о канале есть в БД
                            {
                                $offsetMinID = $peerExist["lastMessageID"];

                                $offset = 0;

                                while (true)
                                {
                                    $msgs = yield $MadelineProto->messages->getHistory([
                                        'peer' => $peer,
                                        'offset_id' => 0,
                                        'offset_date' => 0,
                                        'add_offset' => $offset,
                                        'limit' => 100,
                                        'max_id' => 0,
                                        'min_id' => $offsetMinID,
                                        'hash' => 0
                                    ]);

                                    $counter = 0;

                                    foreach ($msgs["messages"] as $msg)
                                    {
                                        if (array_key_exists('message', $msg))
                                        {
                                            if (!empty($msg['message']))
                                            {
                                                try {
                                                    DB::table('messages')->insert(
                                                        [
                                                            'channelID' => $peer['channel_id'],
                                                            'id' => $msg['id'],
                                                            'text' => $msg['message']
                                                        ]
                                                    );
                                                }
                                                catch (Exception $e)
                                                {

                                                }
                                            }
                                        };
                                    };

                                    if (count($msgs) > 0)
                                    {
                                        $offset += count($msgs);
                                    }
                                    else
                                    {
                                        break;
                                    }
                                }

                                $msgs = yield $MadelineProto->messages->getHistory([
                                    'peer' => $peer,
                                    'offset_id' => 0,
                                    'offset_date' => 0,
                                    'add_offset' => 0,
                                    'limit' => 1,
                                    'max_id' => 0,
                                    'min_id' => 0,
                                    'hash' => 0
                                ]);

                                $msg = $msgs['messages'][0]['id'];

                                DB::table('channels')
                                    ->where('id', $peer['channel_id'])
                                    ->update(['lastMessageID' => $msg]);
                            }
                        }
                    }
                };
            };
        });
    }
}

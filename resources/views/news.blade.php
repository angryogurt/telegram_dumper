@extends('layouts.app')

@section('content')

<?php
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';

    ///TODO: Hide auth data to conf/env

    /// 1. INPUT ACC API ID, HASH
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
        /// 5. GOTO localhost/show_news
        /// 6. AFTER ERROR INPUT CODE FROM TELEGRAM
        /// 7. GOTO localhost/show_news
        /// 8. AFTER LOGIN COMMENT AGAIN
        /*
        yield $MadelineProto->phoneLogin('+79990000000');
        $authorization = yield $MadelineProto->completePhoneLogin('00000');
        */

        foreach (yield $MadelineProto->getDialogs() as $peer)
        {
            if (array_key_exists('_', $peer) and $peer['_'] == "peerChannel")
            {
                if (array_key_exists('channel_id', $peer))
                {
                    $messages = yield $MadelineProto->messages->getHistory([
                        'peer' => $peer,
                        'offset_id' => 0,
                        'offset_date' => 0,
                        'add_offset' => 0,
                        'limit' => 100,
                        'max_id' => 0,
                        'min_id' => 0,
                        'hash' => 0
                    ]);

                    foreach ($messages["messages"] as $message)
                    {
                        if (array_key_exists('message', $message))
                        {
                            echo $peer['channel_id']."-".$message['id']."-".$message['message']."<br>";
                        }
                    }
                }
            }
        }
    });
?>

@endsection

@section('page_title','Парс новостей')
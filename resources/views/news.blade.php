@extends('layouts.app')

@section('content')

<?php
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
                        $msgs = yield $MadelineProto->messages->getHistory([
                            'peer' => $peer,
                            'offset_id' => 0,
                            'offset_date' => 0,
                            'add_offset' => 0,
                            'limit' => 100,
                            'max_id' => 0,
                            'min_id' => 0,
                            'hash' => 0
                        ]);

                        foreach ($msgs["messages"] as $msg)
                        {
                            if (array_key_exists('message', $msg))
                            {
                                echo $peer['channel_id']."-".$msg['id']."-".$msg['message'];
                                echo "<br>";
                            };
                        };
                    }
                }
            };
        };
    });
?>

@endsection

@section('page_title','Парс новостей')
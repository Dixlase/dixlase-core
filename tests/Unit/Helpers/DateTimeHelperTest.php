<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Tests\Unit\Helpers;

use App\Helpers\DateTimeHelper;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateTimeHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ConfigHelper::getFromDatabase は INSTALLED=true の場合のみ DB を読むため、テスト中は明示的に有効化する
        // phpunit.xml が $_ENV/$_SERVER 経由で false を設定しているため、3 経路すべて上書きする
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        // 副作用を残さないよう、設定値を都度クリーンアップする
        SiteSetting::query()->where('name', 'display_timezone')->delete();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_ut_c入力を_asia_tokyoに変換する(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $result = DateTimeHelper::display('2026-04-24 00:00:00', 'datetime');

        // UTC 0:00 は JST 9:00
        $this->assertSame('2026-04-24 09:00', $result);
    }

    public function test_d_bに値がない場合は_asia_tokyoにフォールバックする(): void
    {
        // SiteSetting に display_timezone を設定しない

        $result = DateTimeHelper::display('2026-04-24 00:00:00', 'datetime');

        // フォールバック先は Asia/Tokyo（ConfigHelper のデフォルト）
        $this->assertSame('2026-04-24 09:00', $result);
    }

    public function test_ds_t境界を正しく扱う(): void
    {
        // America/New_York: 2026-03-08 02:00 EST → 03:00 EDT に切り替わる
        $this->setDisplayTimezone('America/New_York');

        // DST 切り替え直前: UTC 2026-03-08 06:30 = EST 01:30
        $beforeDst = DateTimeHelper::display('2026-03-08 06:30:00', 'full');
        $this->assertSame('2026-03-08 01:30:00', $beforeDst);

        // DST 切り替え直後: UTC 2026-03-08 07:30 = EDT 03:30（02:00-03:00 はスキップ）
        $afterDst = DateTimeHelper::display('2026-03-08 07:30:00', 'full');
        $this->assertSame('2026-03-08 03:30:00', $afterDst);
    }

    public function test_null入力はnullを返す(): void
    {
        $this->assertNull(DateTimeHelper::display(null));
        $this->assertNull(DateTimeHelper::display(''));
        $this->assertNull(DateTimeHelper::toIsoUtc(null));
    }

    public function test_carbonインスタンスを受け付ける(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $carbon = Carbon::parse('2026-04-24 00:00:00', 'UTC');
        $this->assertSame('2026-04-24 09:00', DateTimeHelper::display($carbon));
    }

    public function test_carbon_immutableインスタンスを受け付ける(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $immutable = CarbonImmutable::parse('2026-04-24 00:00:00', 'UTC');
        $this->assertSame('2026-04-24 09:00', DateTimeHelper::display($immutable));
    }

    public function test_unixタイムスタンプを受け付ける(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        // 2026-04-24 00:00:00 UTC のエポック秒
        $epoch = 1776988800;
        $this->assertSame('2026-04-24 09:00', DateTimeHelper::display($epoch));
    }

    public function test_既知のフォーマット名を解決する(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertSame('2026-04-24', DateTimeHelper::display('2026-04-24 00:00:00', 'date'));
        $this->assertSame('2026-04-24 09:00', DateTimeHelper::display('2026-04-24 00:00:00', 'datetime'));
        $this->assertSame('2026-04-24 09:00:00', DateTimeHelper::display('2026-04-24 00:00:00', 'full'));
    }

    public function test_カスタムフォーマット文字列を受け付ける(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertSame('2026/04/24 09:00', DateTimeHelper::display('2026-04-24 00:00:00', 'Y/m/d H:i'));
    }

    public function test_不正な文字列はnullを返す(): void
    {
        $this->assertNull(DateTimeHelper::display('not a date'));
    }

    public function test_to_iso_utcは常に_ut_cの_is_o8601を返す(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        // Asia/Tokyo を表示 TZ にしていても、toIsoUtc は UTC を返す
        $iso = DateTimeHelper::toIsoUtc('2026-04-24 00:00:00');
        $this->assertSame('2026-04-24T00:00:00+00:00', $iso);
    }

    public function test_display_timezoneが現在の_d_b値を返す(): void
    {
        $this->setDisplayTimezone('America/New_York');
        $this->assertSame('America/New_York', DateTimeHelper::displayTimezone());

        $this->setDisplayTimezone('Europe/Berlin');
        $this->assertSame('Europe/Berlin', DateTimeHelper::displayTimezone());
    }

    private function setDisplayTimezone(string $tz): void
    {
        SiteSetting::setValue('display_timezone', $tz);
    }
}

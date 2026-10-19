{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc. and Dixlase contributors
https://exc-d.com

Dixlase is dual-licensed. You may use this file under either:

  (a) the GNU Affero General Public License version 3 or later, as
      published by the Free Software Foundation, together with the
      Dixlase Plugin and Theme Exception (see
      LICENSE-EXCEPTIONS for full exception terms); or

  (b) a commercial license agreement obtained from exc-D inc.
      (see LICENSE-COMMERCIAL, or contact info@dixlase.org).

Unless you have entered into a commercial license agreement, this
file is governed by the AGPL terms below.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

{{-- Active admin sessions (#674), shared by the member edit page and the
     site-wide list. Expects $sessions (rows from MemberSessionService),
     $showMember and $viewOnly. A session is addressed by its handle, never
     by its id: the id is the session cookie's value. --}}
@if(count($sessions) === 0)
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin/members/sessions.empty') }}</p>
@else
    <div class="responsive-table !border-0 !dark:border-0">
        <table class="border rounded-sm">
            <caption class="sr-only">{{ __('admin/members/sessions.caption') }}</caption>
            <thead>
                <tr>
                    @if($showMember)
                        <th>{{ __('admin/members/sessions.columns.member') }}</th>
                    @endif
                    <th>{{ __('admin/members/sessions.columns.device') }}</th>
                    <th>{{ __('admin/members/sessions.columns.ip_address') }}</th>
                    <th>{{ __('admin/members/sessions.columns.started_at') }}</th>
                    <th>{{ __('admin/members/sessions.columns.last_activity') }}</th>
                    <th>{{ __('admin/members/sessions.columns.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                    <tr>
                        @if($showMember)
                            <td data-label="{{ __('admin/members/sessions.columns.member') }}">
                                <a href="{{ route('admin.members.edit', ['member' => $session['member_id']]) }}" class="hover:underline">
                                    {{ $session['display_name'] ?? $session['account_name'] }}
                                </a>
                                @if($session['display_name'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $session['account_name'] }}</span>
                                @endif
                            </td>
                        @endif
                        <td data-label="{{ __('admin/members/sessions.columns.device') }}">
                            <span title="{{ $session['user_agent'] }}">{{ $session['device'] !== '' ? $session['device'] : __('admin/members/sessions.unknown') }}</span>
                            @if($session['current'])
                                <x-ui-status-badge variant="blue" :label="__('admin/members/sessions.current')" />
                            @endif
                        </td>
                        <td data-label="{{ __('admin/members/sessions.columns.ip_address') }}">{{ $session['ip_address'] ?? __('admin/members/sessions.unknown') }}</td>
                        <td data-label="{{ __('admin/members/sessions.columns.started_at') }}">{{ $session['started_at']?->format('Y-m-d H:i') ?? __('admin/members/sessions.unknown') }}</td>
                        <td data-label="{{ __('admin/members/sessions.columns.last_activity') }}">
                            <span title="{{ $session['last_activity']->format('Y-m-d H:i:s') }}">{{ $session['last_activity']->diffForHumans() }}</span>
                        </td>
                        <td data-label="{{ __('admin/members/sessions.columns.actions') }}">
                            @unless($session['current'])
                                <form method="POST" action="{{ route('admin.members.sessions.revoke', ['member' => $session['member_id'], 'session' => $session['handle']]) }}" class="flex justify-end">
                                    @csrf
                                    <x-form-button
                                        type="submit"
                                        variant="warning"
                                        size="sm"
                                        icon="fas fa-sign-out-alt"
                                        :label="__('admin/members/sessions.revoke')"
                                        :disabled="$viewOnly"
                                        :title="$viewOnly ? __('common.view_only_action_disabled') : ''"
                                    />
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

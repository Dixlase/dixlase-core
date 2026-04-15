{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

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

@extends('layouts.admin')

@section('content')
    <x-revision.list
        :revisions="$revisions"
        :typeLabels="$typeLabels"
        :retention="$retention"
        :protectedCount="$protectedCount"
        :backRoute="route('admin.front.edit')"
        showRouteName="admin.front.revisions.show"
        restoreRouteName="admin.front.revisions.restore"
        protectRouteName="admin.front.revisions.protect"
        translationPrefix="admin/front/revisions"
    />
@endsection

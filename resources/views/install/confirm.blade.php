<h1>Confirm Settings</h1>
<ul>
    <li>Site Name: {{ $data['site_name'] }}</li>
    <li>Admin Email: {{ $data['admin_email'] }}</li>
    <!-- 他の項目も表示 -->
</ul>
<form action="{{ url('/install/confirm') }}" method="POST">
    @csrf
    <button type="submit">Install</button>
</form>

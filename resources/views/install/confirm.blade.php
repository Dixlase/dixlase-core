<h1>Confirm Settings</h1>
<ul>
    <li>Site Name: {{ $data['site_name'] }}</li>
    <li>Admin Email: {{ $data['admin_email'] }}</li>
    <li>DB Host: {{ $data['db_host'] }}</li>
    <li>DB Database: {{ $data['db_database'] }}</li>
    <li>DB Username: {{ $data['db_username'] }}</li>

</ul>
<form action="{{ url('/install/confirm') }}" method="POST">
    @csrf
    <button type="submit">Install</button>
</form>

<!DOCTYPE html>
<html>
<head>
    <title>Audit Logs</title>
</head>
<body>
    <h1>Audit Logs</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Table</th>
                <th>Action</th>
                <th>Performed By</th>
                <th>Performed On</th>
                <th>Old Data</th>
                <th>New Data</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->table_name }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->performedBy->name ?? 'N/A' }}</td>
                    <td>{{ $log->performedOn->name ?? 'N/A' }}</td>
                    <td>{{ $log->old_data }}</td>
                    <td>{{ $log->new_data }}</td>
                    <td>{{ $log->created_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
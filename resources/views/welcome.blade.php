<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
</head>
<body>
    <p>Welcome to my website!</p>
    <script>
        // Redirect to the upload CSV page
        window.location.href = "{{ route('upload-csv') }}";
    </script>
</body>
</html>

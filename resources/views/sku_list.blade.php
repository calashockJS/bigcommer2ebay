<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKU List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <h2 class="mb-3">Product SKU List in Bigcommerce</h2>
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>SL No</th>
                <th>Product SKU</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($skus as $index => $sku)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $sku }}</td>
                    <td><a href="{{ url('/api/ebay/bc-sku-to-ebay-listing/'.urlencode($sku)) }}" class="btn btn-primary">Create Product in eBay</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No SKUs Found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>

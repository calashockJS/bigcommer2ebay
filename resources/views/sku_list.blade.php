<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKU List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container mt-4">


    <div class="container">
        @if (\Session::has('msg'))
        <div class="row">
            <div class="col-2">&nbsp;</div>
            <div class="col-8">
                <div class="alert alert-success">
                    <ul style="list-style-type: none; padding-left: 0;">
                        <li><i class="fas fa-check-circle text-success"></i> {!! \Session::get('msg') !!}</li>
                    </ul>
                </div>
            </div>
            <div class="col-2">&nbsp;</div>
        </div>
        @endif
        @if ($errors->any())
        <div class="row">
            <div class="col-3">&nbsp;</div>
            <div class="col-6">
                <div class="alert alert-danger">
                    <ul style="list-style-type: none; padding: 0;">
                        @foreach ($errors->all() as $error)
                        <li><i class="fas fa-times-circle text-danger"></i> &nbsp; {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-3">&nbsp;</div>
        </div>
        @endif
        <div class="row">
            <div class="col-6">
                <h2 class="mb-3">Product SKU List in Bigcommerce</h2>
            </div>
            <div class="col-3"><a href="{{ url('/api/ebay/auth') }}" class="btn btn-primary">Update eBay Access Token</a></div>
            <div class="col-3"><a href="{{ url('/bc2ebay/sync-products') }}" class="btn btn-primary">Sync Product BigCommerce to eBay</a></div>
        </div>

        <div class="row">
            <div class="col">
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
                            <td>
                                <a href="{{ url('/ebay/bc-sku-to-ebay-listing/'.urlencode($sku)) }}" class="btn btn-primary">Create Product in eBay</a> &nbsp; &nbsp;
                                <a href="{{ url('/ebay/bc-sku-remove/'.urlencode($sku)) }}" class="btn btn-primary">Remove BC Sku</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No SKUs Found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
@extends('layouts.app')

@section('content')

    <div class="container undesirable-affiliate-table-container">
        <h2>Affiliate info <a class="btn btn-info btn-back-to-tool" href="{{ URL::previous() }}"> < Back to tool</a></h2>

        <table id="undesirable_affiliate_info" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Affiliate Name</th>
                <th>Affiliate ID</th>
                <th>Date Added</th>
                <th>Affiliate Country</th>
                <th>Affiliate Type</th>
                <th>Affiliate Size</th>
                <th>Affiliate Status</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>{{ $affiliate['aff_first_name'] }} {{ $affiliate['aff_last_name'] }}</td>
                <td>{{ $affiliate['affiliate_id'] }}</td>
                <td>{{ $affiliate['date_added'] }}</td>
                <td>{{ $affiliate['country_code'] }}</td>
                <td>{{ $affiliate['aff_type'] }}</td>
                <td>{{ $affiliate['aff_size'] }}</td>
                <td>{{ $affiliate['aff_status'] }}</td>
            </tr>
            </tbody>
        </table>

        <h2>Stats by countries:</h2>
        <table id="undesirable_affiliate_country_data" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Country</th>
                <th>Total Sales 126</th>
                <th>Total Cost 126</th>
                <th>Gross Margin 126</th>
                <th>Num Disputes 126</th>
                <th>Desirability Score</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($affiliate_country_data as $row)
                <tr>
                    <td>{{ $row->country_code }}</td>
                    <td>{{ $row->total_sales_126 }}</td>
                    <td>${{ $row->total_cost_126 }}</td>
                    <td>{{ round($row->gross_margin_126, 2) }}%</td
                    <td>{{ $row->num_disputes_126 }}</td>
                    <td>{{ $row->desirability_scores }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection

@extends('layouts.app')

@section('content')
    <style>
        .table-bordered {
            width: 100%;
            font-size: 10px;
        }
        .table-bordered th,
        .table-bordered td {
            text-align: center;
        }
        .form-control {
            background-color: #fff;
            height: 20px;
            padding: 0;
            text-align: center;
            border: 0;
            border-radius: 0;
        }
        .glyphicon-header {
            font-size: 16px;
            color: blue;
        }
    </style>
    <div class="content">
        <table class="table-bordered">
            <thead>
            <tr class="bg-primary">
                <th>Name</th>
                <th>Id</th>
                <th>Date added</th>
                <th>Affiliate type</th>
                <th>Affiliate size</th>
                <th>Status</th>
                <th>Review date</th>
                <th>Price</th>
                <th>Total Sale (126)</th>
                <th>Total Cost (126)</th>
                <th>Gross Margin (126)</th>
                <th>Disputes (126)</th>
                <th>Dispute% (126)</th>
                <th>Desirability Score</th>
                <th>Workout Program</th>
                <th>Updated Price Plan</th>
                <th>Updated Price</th>
                <th>Workout Time Period</th>
                <th>WP Set Date</th>
                <th>Status</th>
                <th>Email</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>544554345</td>
                <td>Inga Lipt</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>
                    <select class="form-control">
                        <option disabled selected value>-choose wp-</option>
                        <option class="bg-warning">1</option>
                        <option class="bg-danger">2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>2016-10-10</td>
            </tr>
            <tr>
                <td>54345</td>
                <td>Den Brown</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>
                    <select class="form-control">
                        <option disabled selected value>-choose wp-</option>
                        <option class="bg-warning">1</option>
                        <option class="bg-danger">2</option>
                        <option class="bg-success">3</option>
                        <option class="bg-primary">4</option>
                    </select>
                </td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>10</td>
                <td>2016-10-10</td>
            </tr>
            </tbody>
        </table>
    </div>
@endsection
@extends('layouts.opendata')
@section('content')
<h1 class="h5">Assessment Form</h1>
<div class="alert alert-info">
  Placeholder UI. API endpoints are implemented:
  <ul class="mb-0">
    <li>GET <code>/api/trx/form?periodid=...&country_code=...</code></li>
    <li>POST <code>/api/trx/form</code></li>
  </ul>
  The GET response includes computed columns (counts, C1-C3, C, O) and persisted summaries.
</div>
@endsection

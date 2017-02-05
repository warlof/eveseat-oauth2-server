@extends('web::layouts.grids.4-8')

@section('title', trans('oauth2::seat.oauth2_admin'))
@section('page_header', trans('oauth2::seat.oauth2_admin'))

@section('left')

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">{{ trans('oauth2::seat.update_client') }}</h3>
    </div>
    <div class="panel-body">

      <form role="form" action="{{ route('oauth2-admin.clients.update', [$client->id]) }}" method="post" id="client-form">
        {{ csrf_field() }}
        {{ method_field('PUT') }}

        <div class="box-body">

          <div class="form-group">
            <label for="comment">{{ trans('oauth2::seat.name') }}</label>
            <input type="text" name="name" class="form-control" id="name" value="{{ $client->name }}">
          </div>

          <div class="form-group">
            <label for="comment">{{ trans('oauth2::seat.client_id') }}</label>
            <input type="text" name="id" class="form-control" id="id" value="{{ $client->id }}" readonly="readonly">
          </div>

          <div class="form-group">
            <label for="text">{{ trans('oauth2::seat.client_secret') }}</label>
            <input type="text" name="secret" class="form-control" id="secret" value="{{ $client->secret }}" readonly="readonly">
          </div>

          <div class="form-group">
            <label for="redirect">{{ trans_choice('oauth2::seat.redirect_uri', 1) }}</label>
            <input type="url" name="redirect" class="form-control" id="redirect" value="{{ $client->redirect }}" />
          </div>

        </div>
        <!-- /.box-body -->

        <div class="box-footer">
          <button type="submit" class="btn btn-primary pull-right">
            {{ trans('oauth2::seat.update') }}
          </button>
        </div>
      </form>

    </div>
  </div>

@stop

@section('right')
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">{{ trans_choice('oauth2::seat.token', 2) }}</h3>
    </div>
    <div class="panel-body">
      <table class="table table-condensed table-hover" id="tokens">
        <thead>
          <tr>
            <th>{{ trans('oauth2::seat.name') }}</th>
            <th>{{ trans('oauth2::seat.created_at') }}</th>
            <th>{{ trans_choice('oauth2::seat.scope', 2) }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($client->tokens as $token)
          @if($token->revoked)
          <tr class="danger" data-token-id="{{ $token->id }}">
          @else
          <tr data-token-id="{{ $token->id }}">
          @endif
            <td>{{ $token->name }}</td>
            <td>{{ $token->created_at }}</td>
            <td>
              @foreach($availableScopes as $scope)
              @if(in_array($scope->id, $token->scopes))
              <dl>
                <dt>{{ $scope->id }}</dt>
                <dd>{{ $scope->description }}</dd>
              </dl>
              @endif
              @endforeach
            </td>
            <td>
              <form method="post" action="{{ route('oauth2-admin.token.revoke', ['oauth_token' => $token]) }}">
                {{ csrf_field() }}
                <button type="button" class="btn btn-xs btn-default">Show</button>
                <button type="submit" class="btn btn-xs btn-danger">Revoke</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="panel-footer">
      {{ count($client->tokens) }} {{ trans_choice('oauth2::seat.token', count($client->tokens)) }}
    </div>
  </div>

  <div class="modal fade" tabindex="-1" role="dialog" id="token-modal">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title">{{ trans_choice('oauth2::seat.token', 1) }}</h4>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
@stop

@push('javascript')
  <script type="application/javascript">
    var tokensTable = $('#tokens');

    tokensTable.find('tr td button.btn-default').click(function(){
        var modal = $('#token-modal');
        modal.find('.modal-body').html('<p>' +
            $(this).closest('tr').attr('data-token-id') + '</p>');
        modal.modal();
    });
  </script>

@endpush
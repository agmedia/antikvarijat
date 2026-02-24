@extends('emails.layouts.base')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding: 20px 20px 10px 20px; font-family: sans-serif; font-size: 18px; font-weight: bold; line-height: 20px; color: #555555; text-align: center;">
                Nova prijava s forme "Otkup knjiga".
            </td>
        </tr>
        <tr>
            <td style="padding: 20px 20px 0 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555;">
                <table cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="width: 35%">Ime i prezime:</td>
                        <td><b>{{ $requestData['full_name'] }}</b></td>
                    </tr>
                    <tr>
                        <td>Poštanski broj:</td>
                        <td><b>{{ $requestData['postal_code'] }}</b></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td><b>{{ $requestData['email'] }}</b></td>
                    </tr>
                    <tr>
                        <td>Kontakt broj:</td>
                        <td><b>{{ $requestData['phone'] }}</b></td>
                    </tr>
                    <tr>
                        <td>ID prijave:</td>
                        <td><b>{{ $requestData['submission_id'] }}</b></td>
                    </tr>
                    <tr>
                        <td>Vrijeme slanja:</td>
                        <td><b>{{ $requestData['submitted_at'] }}</b></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="padding: 20px 20px 8px 20px; font-family: sans-serif; font-size: 15px; line-height: 20px; color: #555555; font-weight: bold;">
                Fotografije
            </td>
        </tr>

        <tr>
            <td style="padding: 0 20px 10px 20px; font-family: sans-serif; font-size: 14px; line-height: 20px; color: #555555;">
                <ol style="margin: 0; padding-left: 20px;">
                    @foreach($requestData['photos'] as $photo)
                        <li style="margin-bottom: 6px;">
                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener">{{ $photo['name'] }}</a>
                        </li>
                    @endforeach
                </ol>
            </td>
        </tr>
    </table>
@endsection

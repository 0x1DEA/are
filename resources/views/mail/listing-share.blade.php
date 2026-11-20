<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center">
            <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="padding-top:15px;">
                <tr>
                    <td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
                        <table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0"
                               role="presentation">
                            <tr>
                                <td class="content-cell">
                                    <h1 style="font-size:28px;text-align: center;">{{ $data['sender_name'] }} sent you a listing!</h1>

                                    <p style="text-align: center;">{{ $data['sender_name'] }} sent you a listing on AmericaRealEstateInc.com</p>

                                    @if($listing->thumbnail)
                                        <img style="border-radius: 7px;" src="{{ config('app.url') . '/storage/' . $listing->thumbnail?->url }}"
                                             alt="" />
                                    @endif

                                    <h2 style="text-align: center;margin-top:15px;">{{ join(' ', [
    $listing->address_number,
    $listing->address_direction,
    $listing->address_street,
    $listing->address_street_suffix,
    $listing->address_unit,
    $listing->address_city,
    $listing->address_state,
    $listing->address_postal,
]) }}</h2>

                                    <h3 style="text-align: center">{{ join(' · ', [
    $listing->sale_price ? '$' . number_format($listing->sale_price) : '',
    $listing->bedrooms ? $listing->bedrooms . ' Bed' : null,
    $listing->total_bathrooms ? $listing->total_bathrooms . ' Bath' : null,
    $listing->living_area_sq_ft ? $listing->living_area_sq_ft . ' Sqft.' : null,
]) }}</h3>

                                    @if($listing->media_count > 0)
                                        <p style="text-align: center;margin-top:5px;">+{{ $listing->media_count }} more
                                            photos</p>
                                    @endif

                                    <x-mail::button :url="route('share.show', $share->id)">
                                        View Listing
                                    </x-mail::button>

                                    @if($data['message'])
                                        <x-mail::subcopy>
                                            <h2>Message from {{ $data['sender_name'] }}</h2>
                                            <x-mail::panel>
                                                {{ $data['message'] ?? 'No message' }}
                                            </x-mail::panel>
                                        </x-mail::subcopy>
                                    @endif

                                    <p style="font-size: small">This email was sent automatically on behalf
                                        of <span style="background-color: whitesmoke; border-radius: 7px; padding: 3px 7px;margin:0 2px;">{{ $data['sender_name'] }} &lt;{{ $data['sender_email'] }}&gt;</span> not America Real Estate Inc.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div class="footer" style="padding: 0 20px;">
                <br />
                <p style="text-align: left">This property listed by {{ $listing->mls_data['ListAgentFullName'] }}
                    at {{ $listing->mls_data['ListOfficeName'] }} {{ $listing->mls_data['ListAgentMobilePhone'] }}</p>
                <p style="text-align: left">Listings courtesy of MRED as distributed by MLS GRID</p>
                <p style="text-align: left">
                    Based on information submitted to the MLS GRID as of {{ $listing->mls_fetched_at }}. All data is
                    obtained from various sources and
                    may not have been verified by broker or MLS GRID. Supplied Open House Information is subject to
                    change without notice. All information should be independently reviewed and verified for accuracy.
                    Properties may or may not be listed by the office/agent presenting the information
                </p>
                <p style="text-align: left">
                    The Digital Millennium Copyright Act of 1998, 17 U.S.C. § 512 (the “DMCA”) provides recourse for
                    copyright owners who believe that material appearing on the Internet infringes their rights under
                    U.S. copyright law. If you believe in good faith that any content or material made available in
                    connection with our website or services infringes your copyright, you (or your agent) may send us a
                    notice requesting that the content or material be removed, or access to it blocked. Notices must be
                    sent in writing by email to: info@americarealestateinc.com
                </p>
                <p style="text-align: left">Website managed by America Real Estate Inc. IDFPR 478009186</p>
                <p style="text-align: left">© {{ date('Y') }} America Real Estate
                    Inc. {{ __('All rights reserved.') }}</p>
                <br />
            </div>
        </td>
    </tr>
</table>
</body>
</html>

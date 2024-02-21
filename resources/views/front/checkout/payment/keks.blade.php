<div class="d-block w-100">
<div class="row text-center d-none d-sm-block">
    <div class="col-12">
        <img src="{{ $data['logo'] }}" style="height: 90px; margin-bottom:30px;" alt="KEKSPAY" />
        <h5>Plaćanje putem KEKS Pay aplikacije</h5>
    </div>
    <div class="col-12">
        <ul style="list-style: none;padding-left: 0;">
            <li>1. Otvori KEKS Pay</li>
            <li>2. Pritisni <img src="{{ asset('media/img/plusikona.svg') }}" style="height: 40px;"/> ikonicu</li>
            <li>3. Pritisni Skeniraj QR kod</li>
            <li>4. Skeniraj QR kod</li>
        </ul>
    </div>
    <div class="col-sm-12">
        <img src="{{ $data['qr_img'] }}" alt="QR Kod">
    </div>
</div>
<div class="clearfix"></div>
<form action="{{ $data['action'] }}" method="get">
    <input type="hidden" name="qr_type" value="{{ $data['qr_code'] }}">
    <input type="hidden" name="cid" value="{{ $data['cid'] }}">
    <input type="hidden" name="tid" value="{{ $data['tid'] }}">
    <input type="hidden" name="bill_id" value="{{ $data['bill_id'] }}">
    <input type="hidden" name="amount" value="{{ $data['amount'] }}">
    <input type="hidden" name="store" value="{{ $data['store'] }}">
    <input type="hidden" name="success_url" value="{{ $data['success_url'] }}">
    <input type="hidden" name="fail_url" value="{{ $data['fail_url'] }}">

</form>
<div class="clearfix"></div>
<form action="{{ $data['deep_link'] }}" method="get">
    <input type="hidden" name="qr_type" value="{{ $data['qr_code'] }}">
    <input type="hidden" name="cid" value="{{ $data['cid'] }}">
    <input type="hidden" name="tid" value="{{ $data['tid'] }}">
    <input type="hidden" name="bill_id" value="{{ $data['bill_id'] }}">
    <input type="hidden" name="amount" value="{{ $data['amount'] }}">
    <input type="hidden" name="store" value="{{ $data['store'] }}">
    <input type="hidden" name="success_url" value="{{ $data['success_url'] }}">
    <input type="hidden" name="fail_url" value="{{ $data['fail_url'] }}">
    <div class="d-grid gap-2 kekspay">
        <input type="submit" value="Potvrdi" class="btn btn-primary d-block d-sm-none" />
    </div>
</form>
<div class="clearfix"></div>
<div class="row text-center mt-4 appblock">
    <div class="col-12">
        <h5 >Još nemaš KEKS Pay?</h5>
        <a href="https://itunes.apple.com/hr/app/keks-pay/id1434843784?l=hr&mt=8">
            <img style="width:150px" src="{{ asset('media/img/appstore.svg') }}" class="getfrom"/>
        </a>
        <a href="https://play.google.com/store/apps/details?id=agency.sevenofnine.erstewallet.production">
            <img style="width:150px" src="{{ asset('media/img/googleplay.svg') }}" class="getfrom"/>
        </a>
    </div>
</div>
</div>

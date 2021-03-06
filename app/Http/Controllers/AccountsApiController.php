<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Ctrader;
use App\Ctrader\AccountsApi;
use App\Models\CtraderAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class AccountsApiController extends Controller
{
// todo add filter for customizing the return data set

// todo save to database

// todo method to check whether to add new dataset to database

// todo method to reset database

    /**
     * @var mixed
     */
    public $api;

    /**
     * @var mixed
     */
    public $token;

    /**
     * @param AccountsApi $api
     */
    public function __construct(AccountsApi $api)
    {
        $this->api = $api;
        $this->middleware(function ($request, $next) {
            $this->token = optional(Auth::user()->ctraderToken)->access_token;

            return $next($request);
        });
    }

    /**
     * @param  $trading_account_id
     * @return mixed
     */
    public function getAccount($trading_account_id)
    {
        return $this->api->token($this->token)->getAccount($trading_account_id)->makeCall();
        // return an inertia response with table
    }

    /**
     * @return mixed
     */
    public function getAccounts($paymaster_id = null)
    {
        // go first to /ctrader/auth then hit this url to update all accounts
        $user    = Auth::user();
        $user_id = $user->id;

        if ($paymaster_id) {
            $this->authorize('manage_user', $user);
            $user        = User::find($paymaster_id);
            $user_id     = $user->id;
            $this->token = $user->CtraderToken->access_token;
        }

        $results    = $this->api->token($this->token)->getAccounts()->makeCall();
        $collection = collect($results['data']);

        $accounts = $collection->when(config('app.env') === false, function ($collection) {
            return $collection->where('live', false);
        })->when(config('app.env') === true, function ($collection) {
            return $collection->where('live', true);
        })->map(function ($item) use ($user_id) {
            $item['paymaster_id']                = $user_id;
            $item['traderRegistrationTimestamp'] = intval($item['traderRegistrationTimestamp']) / 1000;
            return $item;
        })->toArray();
        // delete all Old Record
        CtraderAccount::where('paymaster_id', $user_id)->delete();
        // Insert New Record
        CtraderAccount::insert($accounts);

        // return CtraderAccount::where('paymaster_id', $user_id)->get();
        return redirect()->route('ctrader.accounts.index');
    }

    /**
     * @param  $trading_account_id
     * @return mixed
     */
    public function getCashFlow($trading_account_id)
    {
        $data           = $this->api->token($this->token)->getCashFlow($trading_account_id)->makeCall();
        $cash_flow      = $data['data'];
        $total_deposit  = 0;
        $total_withdraw = 0;
        $total_balance  = 0;

        foreach ($cash_flow as $flow) {
            if ('DEPOSIT' === $flow['type']) {
                $total_deposit += $flow['delta'];
                $total_balance += $flow['delta'];
            } else {
                $total_withdraw += $flow['delta'];
                $total_balance -= $flow['delta'];
            }
        }

        return Inertia::render('Ctrader/CashFlow', [
            'cash_flow'          => $cash_flow,
            'total_deposit'      => $total_deposit,
            'total_balance'      => $total_balance,
            'total_withdraw'     => $total_withdraw,
            'trading_account_id' => $trading_account_id
        ]);
    }

    /**
     * @param $trading_account_id
     */
    public function getDeals($trading_account_id)
    {
        $user = Auth::user();

        $limit = request()->limit ? request()->limit : '25';

        $cursor = request()->cursor ? request()->cursor : null;

        if (!$cursor) {
            Session::forget('cursors');
        }

        $from = request()->from ? request()->from : null;
        $to   = request()->to ? request()->to : null;

        $query = $this->api->token($this->token)->getAccount($trading_account_id);

        if ($limit) {
            $query->limit($limit);
        }

        if ($from) {
            $from = intval(\Carbon\Carbon::parse($from)->timestamp) * 1000;
            $query->from($from);
            $cursor = null;
            Session::forget('cursors');
        }

        if ($to) {
            $to = intval(\Carbon\Carbon::parse($to)->timestamp) * 1000;
            $query->to($to);
            $cursor = null;
            Session::forget('cursors');
        }

        if ($cursor) {
            $query->cursor($cursor);
        }

        $deals = $query->makeCall();
        $next  = null;

        if (isset($deals['next'])) {
            $next = $deals['next'];
        }

        $cursors = [null];

        if (Session::has('cursors')) {
            $cursors = Session::get('cursors');
        }

        $balance = CtraderAccount::where('accountId', $trading_account_id)->first()->balance;

        array_push($cursors, $next);
        $cursors = array_unique($cursors);

        Session::put('cursors', $cursors);
        $cursors = Session::get('cursors');
        return Inertia::render('Ctrader/TradingHistory', [

            'filters'            => [
                'limit'  => $limit,
                'from'   => $from,
                'to'     => $to,
                'cursor' => $cursor
            ],
            'trading_account_id' => $trading_account_id,
            'paymaster'          => $user->paymaster,
            'prev_cursor'        => $cursor,
            'cursors'            => $cursors,
            'next_cursor'        => $next,
            'deals'              => $deals['data'],
            'balance'            => $balance
        ]);
    }

    /**
     * @param  $trading_account_id
     * @return mixed
     */
    public function getSymbols($trading_account_id)
    {
        $data = $this->api->token($this->token)->getSymbols($trading_account_id)->makeCall();
        return $data['data'];
    }

    public function index()
    {
        $user = Auth::user();

        $per_page = 10;

        if (request()->per_page && is_int(intval(request()->per_page)) && request()->per_page > 0) {
            $per_page = intval(request()->per_page);
        }

        return Inertia::render('Ctrader/Index', [
            'paymaster' => $user,
            'filters'   => Request::all('search', 'broker', 'leverage', 'currency', 'live', 'demo', 'status', 'page', 'per_page'),
            'per_page'  => $per_page,
            'accounts'  => CtraderAccount::where('paymaster_id', $user->id)
                ->filter(Request::only('search', 'broker', 'leverage', 'currency', 'live', 'demo', 'status'))
                ->paginate($per_page)
                ->transform(function ($account) {
                    return [
                        'id'                => $account->accountId,
                        'account_no'        => $account->accountNumber,
                        'broker_name'       => $account->brokerName,
                        'broker_title'      => $account->brokerTitle,
                        'live'              => $account->live,
                        'currency'          => $account->depositCurrency,
                        'leverage'          => $account->leverage,
                        'balance'           => $account->balance,
                        'status'            => $account->AccountStatus,
                        'deleted'           => $account->deleted,
                        'swap_free'         => $account->swapFree,
                        'type'              => $account->traderAccountType,
                        'registration_date' => $account->traderRegistrationTimestamp
                    ];
                })
        ]);
    }

    /**
     * This Should be Call on Cron at Daily Interval midnight
     * @param Request $request
     */
    public function updateAllAccounts()
    {
        $ids  = Ctrader::all()->pluck('user_id')->toArray();
        $data = [];

        foreach ($ids as $id) {
            $accounts = $this->getAccounts($id);
            array_push($data, $accounts);
            // call the getAccounts and attach paymaster_id in request
        }

        // this is just for verifaction we are calling it multiple times
        return $data;
    }
}

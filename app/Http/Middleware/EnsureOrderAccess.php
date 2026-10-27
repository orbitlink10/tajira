<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('order');
        $user = $request->user();

        if (! $order) {
            abort(404);
        }

        if ($user?->isAdmin() || ($user && $order->user_id === $user->id)) {
            return $next($request);
        }

        if ($request->session()->get('order_access.'.$order->number) === true) {
            return $next($request);
        }

        return redirect()->route('orders.track')
            ->with('error', 'Enter the order number and email used at checkout to view this order.');
    }
}

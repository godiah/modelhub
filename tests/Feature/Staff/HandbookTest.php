<?php

use App\Models\User;

/*
 * The staff handbook: rendered from resources/handbook, readable by every signed-in staff member, linked from the portal's footer.
 */

it('shows the payments handbook to any signed-in staff member, with a contents list, tables and its sections', function () {
    foreach (['Support', 'Finance', 'Auditor', 'Marketplace moderator', 'Super admin'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->get(route('admin.handbook.show', 'payments'))->assertOk()->assertSee('Payments handbook for staff')->assertSee('On this page')->assertSee('Staff handbook')
            ->assertSee('The five rules')->assertSee('Unconfirmed withdrawals')->assertSee('Escrow refunds')->assertSee('<section id="the-five-rules"', false)->assertSee('href="#daily-routine"', false);
    }
});

it('renders the markdown properly: tables, lists and the draft notice, with no raw markdown left', function () {
    $page = $this->actingAs(staffWith('Finance'), 'staff')->get(route('admin.handbook.show', 'payments'))->assertOk();

    $page->assertSee('<table class="w-full', false)->assertSee('<blockquote class="rounded-xl', false)->assertSee('<ol class="space-y-2.5', false)->assertSee('overflow-x-auto', false)->assertSee('DRAFT for review')
        ->assertDontSee('## ')->assertDontSee('| --- ', false)->assertDontSee('|---|', false);
});

it('links the handbook from the footer of every staff page', function () {
    $this->actingAs(staffWith('Finance'), 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.handbook.show', 'payments'), false)->assertSee('Payments handbook');
});

it('keeps the handbook to signed-in staff and knows no other guides', function () {
    $this->get(route('admin.handbook.show', 'payments'))->assertRedirect(route('admin.login'));

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.handbook.show', 'unknown'))->assertNotFound();
    // A member, signed in as a member, is not staff
    auth('staff')->logout();
    $this->actingAs(User::factory()->create())->get(route('admin.handbook.show', 'payments'))->assertRedirect();
});

it('strips raw html from the markdown, so a handbook can never run script on the page', function () {
    $path = resource_path('handbook/payments.md');
    $original = file_get_contents($path);
    file_put_contents($path, $original."\n\n<script>alert('x')</script>\n\n[bad](javascript:alert(1))\n");
    touch($path, time() + 5);

    try {
        $page = $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.handbook.show', 'payments'))->assertOk();
        $page->assertDontSee("<script>alert('x')</script>", false)->assertDontSee('href="javascript:', false);
    } finally {
        file_put_contents($path, $original);
        touch($path, time() - 5);
    }
});

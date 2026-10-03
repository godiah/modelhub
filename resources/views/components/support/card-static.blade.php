@props(['card'])

{{--
    Renders one support-assistant card (trail, blocker, escrow, ticket, approval) outside the chat panel, from the same JSON
    shape and the same card components, so a payment looks identical in the chat, in "My requests" and in the staff
    evidence. Self-contained (it carries its own tone map) because staff pages do not mount the chat widget.
--}}
<div x-data="{
    card: @js($card),
    tone(name) { return ({ amber: 'bg-amber-100 text-amber-800', green: 'bg-green-100 text-green-800', teal: 'bg-teal-100 text-teal-800', red: 'bg-red-100 text-red-800', neutral: 'bg-neutral-100 text-neutral-700' })[name] || 'bg-neutral-100 text-neutral-700'; },
    run() {},
}" {{ $attributes }}>
    <template x-if="card.type === 'trail'"><x-support.card.trail /></template>
    <template x-if="card.type === 'blocker'"><x-support.card.blocker /></template>
    <template x-if="card.type === 'escrow'"><x-support.card.escrow /></template>
    <template x-if="card.type === 'ticket'"><x-support.card.ticket /></template>
    <template x-if="card.type === 'approval'"><x-support.card.approval /></template>
</div>

<?php

namespace App\Enums;

/** How a ticket was started. Only the assistant's "Talk to a person" exists so far; the contact form and staff-made tickets come later. */
enum SupportTicketSource: string
{
    case Bot = 'bot';
    case ContactForm = 'contact_form';
    case Staff = 'staff';
}

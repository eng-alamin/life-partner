<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Every kind of notification. The stored row keeps only this value and a few short
 * codes; the words come from lang/{locale}/notifications.php when the notification
 * is shown or mailed, so they always follow the reader's language.
 */
enum NotificationType: string
{
    case InterestReceived = 'interest.received';
    case InterestAccepted = 'interest.accepted';
    case InterestDeclined = 'interest.declined';
    case InterestCancelled = 'interest.cancelled';
    case ContactReady = 'interest.contact_ready';
    case BiodataApproved = 'biodata.approved';
    case BiodataRejected = 'biodata.rejected';
    case PhotoApproved = 'photo.approved';
    case PhotoRejected = 'photo.rejected';
    case PaymentApproved = 'payment.approved';
    case PaymentRejected = 'payment.rejected';
    case PaymentRefunded = 'payment.refunded';
    case MembershipExpiring = 'membership.expiring';
    case MembershipExpired = 'membership.expired';
    case StaffPaymentWaiting = 'staff.payment_waiting';
    case StaffBiodataWaiting = 'staff.biodata_waiting';
    case StaffBiodataChanged = 'staff.biodata_changed';
    case StaffPhotoWaiting = 'staff.photo_waiting';
    case StaffReportNew = 'staff.report_new';

    public function category(): NotificationCategory
    {
        return match ($this) {
            self::InterestReceived, self::InterestAccepted, self::InterestDeclined,
            self::InterestCancelled, self::ContactReady => NotificationCategory::Interests,
            self::BiodataApproved, self::BiodataRejected,
            self::PhotoApproved, self::PhotoRejected => NotificationCategory::Profile,
            self::PaymentApproved, self::PaymentRejected, self::PaymentRefunded,
            self::MembershipExpiring, self::MembershipExpired => NotificationCategory::Payments,
            self::StaffPaymentWaiting, self::StaffBiodataWaiting, self::StaffBiodataChanged,
            self::StaffPhotoWaiting, self::StaffReportNew => NotificationCategory::Staff,
        };
    }

    /** The page the notification points to. */
    public function routeName(): string
    {
        return match ($this) {
            self::InterestReceived, self::InterestAccepted, self::InterestDeclined,
            self::InterestCancelled, self::ContactReady => 'interests.index',
            self::BiodataApproved, self::BiodataRejected,
            self::PhotoApproved, self::PhotoRejected => 'my-biodata',
            self::PaymentApproved, self::PaymentRejected, self::PaymentRefunded,
            self::MembershipExpiring, self::MembershipExpired => 'billing.index',
            self::StaffPaymentWaiting => 'admin.payments',
            self::StaffBiodataWaiting, self::StaffBiodataChanged => 'admin.approvals',
            self::StaffPhotoWaiting => 'admin.photos',
            self::StaffReportNew => 'admin.reports',
        };
    }

    /** Staff-only types need this permission, both to be sent and to be opened. */
    public function permission(): ?StaffPermission
    {
        return match ($this) {
            self::StaffPaymentWaiting => StaffPermission::ManagePayments,
            self::StaffBiodataWaiting, self::StaffBiodataChanged, self::StaffPhotoWaiting => StaffPermission::ReviewBiodata,
            self::StaffReportNew => StaffPermission::HandleReports,
            default => null,
        };
    }

    /**
     * Only a short list of urgent events may also become an SMS. SMS costs money and
     * lands on a lock screen, so staff alerts and everyday news stay in-app and email.
     * The SMS words live in lang/{locale}/sms.php under "types" (same langKey).
     */
    public function sendsSms(): bool
    {
        return match ($this) {
            self::InterestAccepted, self::ContactReady,
            self::PaymentApproved, self::PaymentRejected => true,
            default => false,
        };
    }

    /** Key inside lang/{locale}/notifications.php under "types". */
    public function langKey(): string
    {
        return str_replace('.', '_', $this->value);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starting text for the content pages, in Bangla and English. A page that already exists
 * is never touched, so running this again can not overwrite what the owner wrote.
 *
 * The general pages (about, how it works, FAQ) are published. The contact page and the
 * three legal pages (privacy, terms, refund) are NOT: the owner must add real contact
 * details and have a lawyer read the legal text before publishing them in the admin panel.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            if (Page::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $model = new Page;
            $model->forceFill(['slug' => $slug, ...$page])->save();
        }

        Page::forgetPublishedSlugs();
    }

    /** @return array<string, array<string, mixed>> */
    private function pages(): array
    {
        return [
            'about' => [
                'title_bn' => 'আমাদের সম্পর্কে',
                'title_en' => 'About us',
                'is_published' => true,
                'body_bn' => <<<'TXT'
Life Partner একটি ইসলামী বিবাহ সেবা। আমাদের লক্ষ্য, দ্বীনদার মুসলিম নারী-পুরুষ ও তাঁদের পরিবার যেন সম্মান, গোপনীয়তা ও ইসলামী শিষ্টাচার বজায় রেখে জীবনসঙ্গী খুঁজে নিতে পারেন। বাংলাদেশ ও প্রবাসী বাংলাদেশিরা এই সেবা ব্যবহার করতে পারেন।

## আমরা কী বিশ্বাস করি
- বিবাহের আলোচনা অভিভাবকের সম্পৃক্ততায় এগোনো উচিত।
- ব্যক্তিগত তথ্য ও ছবি আপনার অনুমতি ছাড়া কারও সামনে আসা উচিত নয়।
- বায়োডাটায় সত্য তথ্য থাকা জরুরি।

## আমরা কী করি
- প্রতিটি বায়োডাটা প্রকাশের আগে আমাদের দল দেখে অনুমোদন দেয়।
- যোগাযোগের তথ্য শুধু পারস্পরিক সম্মতির পর, নির্ধারিত নিয়মে দেখা যায়।
- সন্দেহজনক বা ভুয়া প্রোফাইলের অভিযোগ আমরা যাচাই করি।

Life Partner শুধু পরিচয় করিয়ে দেওয়ার একটি মাধ্যম। বিবাহের সিদ্ধান্ত নেওয়ার আগে পরিবারের মাধ্যমে নিজ দায়িত্বে খোঁজখবর নিন।
TXT,
                'body_en' => <<<'TXT'
Life Partner is an Islamic matrimony service. Our aim is to help practicing Muslim men, women and their families find a life partner with dignity, privacy and Islamic etiquette. Bangladeshis at home and abroad can use this service.

## What we believe
- Marriage talks should move forward with the involvement of a guardian.
- Personal details and photos should never reach anyone without your permission.
- A biodata must contain true information.

## What we do
- Every biodata is reviewed and approved by our team before it is published.
- Contact details can be seen only after mutual consent, under fixed rules.
- We look into reports about suspicious or fake profiles.

Life Partner is only a means of introduction. Before deciding on a marriage, make your own enquiries through your family.
TXT,
            ],

            'how-it-works' => [
                'title_bn' => 'কীভাবে কাজ করে',
                'title_en' => 'How it works',
                'is_published' => true,
                'body_bn' => <<<'TXT'
## ১. নিবন্ধন করুন
ইমেইল দিয়ে নিবন্ধন করুন এবং পাঠানো কোড দিয়ে ইমেইল যাচাই করুন।

## ২. বায়োডাটা তৈরি করুন
ধাপে ধাপে সাধারণ তথ্য, ঠিকানা, শিক্ষা, পেশা, দ্বীনি অবস্থা, পরিবার, বিবাহ-সংক্রান্ত তথ্য, পছন্দের পাত্র/পাত্রী এবং অভিভাবকের যোগাযোগ দিন। অভিভাবকের তথ্য ডিফল্টভাবে লুকানো থাকে।

## ৩. অনুমোদনের অপেক্ষা
আপনার বায়োডাটা আমাদের দল দেখে অনুমোদন করে। অনুমোদিত বায়োডাটায় পরিবর্তন করলে তা আবার যাচাইয়ে যায়।

## ৪. খুঁজুন ও আগ্রহ জানান
বয়স, উচ্চতা, জেলা বা দেশ, শিক্ষা ও দ্বীনি অবস্থা দিয়ে ছাঁকনি দিন। পছন্দ হলে শর্টলিস্ট করুন বা আগ্রহ পাঠান।

## ৫. সম্মতি ও অভিভাবক
অপর পক্ষ আগ্রহ গ্রহণ করলে এবং প্রয়োজনে তাঁর অভিভাবক সম্মতি দিলে অভিভাবকের যোগাযোগ দেখা যায়। যোগাযোগ অভিভাবকের মাধ্যমেই করুন।

## ছবির গোপনীয়তা
ছবি দেওয়া ঐচ্ছিক। কারা ছবি দেখবে তা আপনি ঠিক করেন: কেউ না, আগ্রহ গৃহীত হওয়ার পর, অথবা অনুমোদিত সদস্যরা।
TXT,
                'body_en' => <<<'TXT'
## 1. Register
Register with your email and confirm it with the code we send.

## 2. Create your biodata
Fill in general details, address, education, occupation, religious practice, family, marriage-related details, partner preferences and your guardian's contact, step by step. Guardian details are hidden by default.

## 3. Wait for approval
Our team reviews and approves your biodata. If you change an approved biodata, it goes back for review.

## 4. Search and send interest
Filter by age, height, district or country, education and religious practice. Shortlist profiles you like, or send an interest.

## 5. Consent and guardian
When the other side accepts, and their guardian agrees where required, the guardian's contact becomes visible. Please keep the contact through the guardian.

## Photo privacy
Photos are optional. You decide who sees them: nobody, only after an interest is accepted, or approved members.
TXT,
            ],

            'faq' => [
                'title_bn' => 'সাধারণ জিজ্ঞাসা',
                'title_en' => 'Frequently asked questions',
                'is_published' => true,
                'body_bn' => <<<'TXT'
### আমার তথ্য কি সবাই দেখতে পায়?
না। অতিথিরা শুধু সীমিত কার্ড দেখেন, ছবি বা যোগাযোগের তথ্য নয়। ফোন, ইমেইল, ঠিকানা ও অভিভাবকের তথ্য সবার সামনে আসে না।

### বায়োডাটা কখন প্রকাশ হয়?
জমা দেওয়ার পর আমাদের দল দেখে অনুমোদন দিলে তবেই তা অনুসন্ধানে আসে। অনুমোদিত না হলে কারণ আপনার বায়োডাটা পাতায় দেখানো হয়।

### কীভাবে যোগাযোগের তথ্য পাব?
আপনি আগ্রহ পাঠান। অপর পক্ষ গ্রহণ করলে এবং অভিভাবকের সম্মতি প্রযোজ্য হলে অভিভাবকের যোগাযোগ দেখার সুযোগ আসে। প্রথমবার বিনা খরচে দেখা যায়; এরপর ক্রেডিট লাগে।

### ক্রেডিট কীভাবে কিনব?
প্ল্যান পাতা থেকে প্ল্যান বেছে নিন এবং নির্দেশ অনুযায়ী bKash-এ টাকা পাঠিয়ে TrxID বা স্ক্রিনশট জমা দিন। আমাদের দল মিলিয়ে দেখে অনুমোদন করলে ক্রেডিট যুক্ত হয়।

### কেউ বিরক্ত করলে কী করব?
প্রোফাইল পাতা থেকে তাকে ব্লক করুন বা অভিযোগ জানান। আমরা অভিযোগ যাচাই করে ব্যবস্থা নিই।

### আমি কি আমার বায়োডাটা লুকাতে পারি?
আপনি বায়োডাটা লুকানো বা বন্ধ রাখার অনুরোধ করতে পারেন। যোগাযোগ পাতা দেখুন।
TXT,
                'body_en' => <<<'TXT'
### Can everyone see my details?
No. Guests see only a limited card, never photos or contact details. Phone, email, address and guardian details are not shown to everyone.

### When is my biodata published?
After you submit it, our team reviews it. It appears in search only once approved. If it is not approved, the reason is shown on your biodata page.

### How do I get contact details?
Send an interest. When the other side accepts, and the guardian agrees where required, you can view the guardian's contact. The first time is free; after that a credit is needed.

### How do I buy credits?
Pick a plan on the plans page, send the money by bKash as instructed and submit the TrxID or a screenshot. Our team checks it and, once approved, the credits are added.

### What if someone bothers me?
Block the person or report the profile from the profile page. We look into reports and act on them.

### Can I hide my biodata?
You can ask us to hide or close your biodata. Please see the contact page.
TXT,
            ],

            'contact' => [
                'title_bn' => 'যোগাযোগ',
                'title_en' => 'Contact us',
                'is_published' => false,
                'body_bn' => <<<'TXT'
প্রশ্ন, অভিযোগ, পেমেন্ট-সংক্রান্ত সমস্যা বা বায়োডাটা লুকানো/মুছে ফেলার অনুরোধ জানাতে আমাদের সাথে যোগাযোগ করুন।

## লেখার সময় জানাবেন
- আপনার বায়োডাটার কোড (থাকলে)
- সমস্যার সংক্ষিপ্ত বিবরণ
- পেমেন্টের ক্ষেত্রে পেমেন্টের কোড

## সতর্কতা
আমরা কখনো আপনার পাসওয়ার্ড বা ওটিপি কোড চাইব না। কেউ চাইলে তা দেবেন না।
TXT,
                'body_en' => <<<'TXT'
Contact us for questions, complaints, payment problems, or to ask us to hide or delete your biodata.

## Please include
- Your biodata code (if you have one)
- A short description of the problem
- For payments, the payment code

## A warning
We will never ask for your password or an OTP code. If someone does, do not give it.
TXT,
            ],

            'privacy' => [
                'title_bn' => 'গোপনীয়তা নীতি',
                'title_en' => 'Privacy policy',
                'is_published' => false,
                'body_bn' => <<<'TXT'
এই নীতিতে বলা হয়েছে Life Partner আপনার কোন তথ্য সংগ্রহ করে, কীভাবে ব্যবহার ও সুরক্ষা করে।

## আমরা কোন তথ্য সংগ্রহ করি
- অ্যাকাউন্টের তথ্য: নাম, ইমেইল, ঐচ্ছিকভাবে ফোন নম্বর।
- বায়োডাটার তথ্য: আপনার দেওয়া ব্যক্তিগত, পারিবারিক, শিক্ষা, পেশা ও দ্বীনি তথ্য।
- অভিভাবকের তথ্য: নাম, সম্পর্ক ও ফোন নম্বর।
- ছবি: আপনি চাইলে দেন।
- পেমেন্টের তথ্য: পাঠানো নম্বর, TrxID ও স্ক্রিনশট।
- প্রযুক্তিগত তথ্য: নিরাপত্তা ও অপব্যবহার ঠেকাতে প্রয়োজনীয় লগ।

## কেন ব্যবহার করি
সেবা চালাতে, বায়োডাটা যাচাই করতে, পেমেন্ট মেলাতে, জরুরি বিজ্ঞপ্তি পাঠাতে এবং ভুয়া বা ক্ষতিকর আচরণ ঠেকাতে।

## কারা দেখতে পারে
- ফোন, ইমেইল, ঠিকানা ও অভিভাবকের তথ্য সবার সামনে দেখানো হয় না।
- অভিভাবকের যোগাযোগ শুধু আগ্রহ গৃহীত হওয়ার পর এবং নির্ধারিত নিয়মে দেখা যায়।
- ছবি কারা দেখবে তা আপনি ঠিক করেন।
- আমাদের কর্মীরা শুধু কাজের প্রয়োজনে ও নিজ নিজ অনুমতির সীমায় তথ্য দেখেন।
- আমরা আপনার তথ্য বিক্রি করি না।

## সুরক্ষা
ফোন নম্বর ও অভিভাবকের ফোন এনক্রিপ্ট করে রাখা হয়। ছবি ও পেমেন্টের স্ক্রিনশট ব্যক্তিগত স্টোরেজে থাকে এবং অনুমতি যাচাই করে দেখানো হয়। সংবেদনশীল তথ্য কার্যকলাপের লগে রাখা হয় না।

## আপনার অধিকার
আপনি আপনার তথ্য বদলাতে পারেন, বায়োডাটা লুকানোর বা মুছে ফেলার অনুরোধ জানাতে পারেন। আইন ও হিসাবের প্রয়োজনে পেমেন্টের রেকর্ড রাখা হতে পারে।

## নীতি বদল
নীতি বদলালে এই পাতায় নতুন সংস্করণ দেওয়া হবে।
TXT,
                'body_en' => <<<'TXT'
This policy explains what information Life Partner collects, how it uses it and how it protects it.

## What we collect
- Account details: name, email and, optionally, a phone number.
- Biodata: the personal, family, education, occupation and religious details you provide.
- Guardian details: name, relation and phone number.
- Photos: only if you add them.
- Payment details: the sending number, TrxID and screenshot.
- Technical records: logs needed for security and to stop abuse.

## Why we use it
To run the service, to review biodatas, to match payments, to send urgent notices and to stop fake or harmful behaviour.

## Who can see it
- Phone, email, address and guardian details are not shown to everyone.
- The guardian's contact can be seen only after an interest is accepted, under fixed rules.
- You decide who may see your photos.
- Our staff see information only when their work needs it and within their permissions.
- We do not sell your information.

## Protection
Phone numbers and guardian phone numbers are stored encrypted. Photos and payment screenshots are kept in private storage and shown only after a permission check. Sensitive details are not written to the activity log.

## Your rights
You can change your information and ask us to hide or delete your biodata. Payment records may be kept where the law or accounting requires it.

## Changes
If this policy changes, the new version will be published on this page.
TXT,
            ],

            'terms' => [
                'title_bn' => 'শর্তাবলি',
                'title_en' => 'Terms of use',
                'is_published' => false,
                'body_bn' => <<<'TXT'
Life Partner ব্যবহার করলে আপনি নিচের শর্তগুলো মেনে নিচ্ছেন।

## কারা ব্যবহার করতে পারবেন
কমপক্ষে ১৮ বছর বয়সী মুসলিম, যিনি নিজের বা পরিবারের সদস্যের বিবাহের উদ্দেশ্যে সেবাটি ব্যবহার করছেন।

## সত্য তথ্য
আপনার বায়োডাটা ও ছবির তথ্য সত্য হতে হবে। অন্যের তথ্য বা ছবি নিজের বলে দেওয়া যাবে না।

## আচরণ
- সম্মান ও ইসলামী শিষ্টাচার বজায় রাখুন।
- আগ্রহের বার্তায় ফোন, ইমেইল বা লিংক দেবেন না।
- বিরক্ত করা, প্রতারণা বা ভুয়া প্রোফাইল নিষিদ্ধ।
- যোগাযোগ অভিভাবকের মাধ্যমে করুন।

## আমাদের ক্ষমতা
আমরা কোনো বায়োডাটা বা ছবি অননুমোদন করতে, লুকাতে বা কোনো অ্যাকাউন্ট স্থগিত করতে পারি, যদি তা শর্ত ভঙ্গ করে বা অন্যদের জন্য ক্ষতিকর হয়।

## সেবার সীমা
Life Partner শুধু পরিচয় করিয়ে দেওয়ার মাধ্যম। বিবাহ হবে বা কোনো তথ্য নির্ভুল, এমন নিশ্চয়তা আমরা দিই না। বিবাহের আগে পরিবারের মাধ্যমে নিজ দায়িত্বে খোঁজখবর নিন।

## পেমেন্ট
প্ল্যানের দাম প্ল্যান পাতায় দেখানো হয়। পেমেন্ট আমাদের দল যাচাই করে অনুমোদন করে। রিফান্ডের নিয়ম রিফান্ড নীতিতে আছে।

## শর্ত বদল
শর্ত বদলালে এই পাতায় নতুন সংস্করণ দেওয়া হবে। বদলের পর সেবা ব্যবহার চালালে নতুন শর্ত মানা হয়েছে ধরা হবে।
TXT,
                'body_en' => <<<'TXT'
By using Life Partner you accept the terms below.

## Who may use it
A Muslim aged 18 or more, using the service for their own marriage or that of a family member.

## True information
Your biodata and photo details must be true. You must not present someone else's details or photos as your own.

## Conduct
- Keep respect and Islamic etiquette.
- Do not put a phone number, email or link in an interest message.
- Harassment, fraud and fake profiles are forbidden.
- Keep the contact through the guardian.

## Our powers
We may reject or hide a biodata or photo, or suspend an account, if it breaks these terms or harms others.

## Limits of the service
Life Partner is only a means of introduction. We do not promise that a marriage will happen or that any detail is accurate. Make your own enquiries through your family before deciding.

## Payments
Plan prices are shown on the plans page. Our team checks and approves each payment. The refund rules are in the refund policy.

## Changes
If these terms change, the new version will be published on this page. Continuing to use the service after a change means you accept the new terms.
TXT,
            ],

            'refund' => [
                'title_bn' => 'রিফান্ড নীতি',
                'title_en' => 'Refund policy',
                'is_published' => false,
                'body_bn' => <<<'TXT'
## পেমেন্ট কীভাবে হয়
আপনি bKash-এ টাকা পাঠিয়ে TrxID বা স্ক্রিনশট জমা দেন। আমাদের দল যাচাই করে অনুমোদন করলে ক্রেডিট বা সদস্যপদ চালু হয়।

## রিফান্ড কখন
- ভুল বা দ্বিগুণ টাকা পাঠালে।
- টাকা পাঠানোর পর যাচাই হয়ে ক্রেডিট বা সদস্যপদ যুক্ত না হলে।

## রিফান্ড কখন নয়
- ব্যবহার করা ক্রেডিট, অর্থাৎ যে ক্রেডিট দিয়ে যোগাযোগ দেখা হয়ে গেছে।
- সদস্যপদের যে সময় পার হয়ে গেছে।

## কীভাবে অনুরোধ করবেন
যোগাযোগ পাতার মাধ্যমে আপনার পেমেন্টের কোড দিয়ে জানান। আমরা যাচাই করে সিদ্ধান্ত জানাব। রিফান্ড অনুমোদিত হলে সাধারণত যে নম্বর থেকে টাকা এসেছিল সেখানে ফেরত দেওয়া হয়।
TXT,
                'body_en' => <<<'TXT'
## How payment works
You send money by bKash and submit the TrxID or a screenshot. Our team checks it and, once approved, your credits or membership start.

## When we refund
- If you sent a wrong or double amount.
- If you paid and, after checking, no credits or membership were added.

## When we do not refund
- Credits that were used, that is, credits already spent to view a contact.
- The part of a membership that has already passed.

## How to ask
Write to us through the contact page with your payment code. We will check and tell you our decision. If a refund is approved, it is normally sent back to the number the money came from.
TXT,
            ],
        ];
    }
}

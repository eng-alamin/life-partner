<?php

return [
    'accepted' => ':attribute গ্রহণ করতে হবে।',
    'array' => ':attribute অবশ্যই একটি তালিকা হতে হবে।',
    'before' => ':attribute অবশ্যই :date-এর আগের তারিখ হতে হবে।',
    'before_or_equal' => ':attribute অবশ্যই :date বা তার আগের তারিখ হতে হবে।',
    'after' => ':attribute অবশ্যই :date-এর পরের তারিখ হতে হবে।',
    'after_or_equal' => ':attribute অবশ্যই :date বা তার পরের তারিখ হতে হবে।',
    'between' => [
        'numeric' => ':attribute অবশ্যই :min থেকে :max-এর মধ্যে হতে হবে।',
        'string' => ':attribute অবশ্যই :min থেকে :max অক্ষরের মধ্যে হতে হবে।',
    ],
    'boolean' => ':attribute হ্যাঁ বা না হতে হবে।',
    'confirmed' => ':attribute নিশ্চিতকরণ মিলছে না।',
    'date' => ':attribute সঠিক তারিখ নয়।',
    'digits' => ':attribute অবশ্যই :digits সংখ্যার হতে হবে।',
    'digits_between' => ':attribute অবশ্যই :min থেকে :max সংখ্যার হতে হবে।',
    'email' => ':attribute একটি সঠিক ইমেইল ঠিকানা হতে হবে।',
    'exists' => 'নির্বাচিত :attribute সঠিক নয়।',
    'file' => ':attribute অবশ্যই একটি ফাইল হতে হবে।',
    'image' => ':attribute অবশ্যই একটি ছবি হতে হবে।',
    'in' => 'নির্বাচিত :attribute সঠিক নয়।',
    'integer' => ':attribute অবশ্যই পূর্ণসংখ্যা হতে হবে।',
    'max' => [
        'numeric' => ':attribute :max-এর বেশি হতে পারবে না।',
        'file' => ':attribute :max কিলোবাইটের বেশি হতে পারবে না।',
        'string' => ':attribute :max অক্ষরের বেশি হতে পারবে না।',
    ],
    'mimes' => ':attribute অবশ্যই :values ধরনের ফাইল হতে হবে।',
    'min' => [
        'numeric' => ':attribute কমপক্ষে :min হতে হবে।',
        'file' => ':attribute কমপক্ষে :min কিলোবাইট হতে হবে।',
        'string' => ':attribute কমপক্ষে :min অক্ষরের হতে হবে।',
    ],
    'numeric' => ':attribute অবশ্যই একটি সংখ্যা হতে হবে।',
    'regex' => ':attribute-এর ফরম্যাট সঠিক নয়।',
    'required' => ':attribute আবশ্যক।',
    'same' => ':attribute এবং :other মিলতে হবে।',
    'size' => [
        'numeric' => ':attribute অবশ্যই :size হতে হবে।',
        'string' => ':attribute অবশ্যই :size অক্ষরের হতে হবে।',
    ],
    'string' => ':attribute অবশ্যই টেক্সট হতে হবে।',
    'unique' => 'এই :attribute আগে থেকেই ব্যবহৃত হয়েছে।',
    'uploaded' => ':attribute আপলোড করা যায়নি।',
    'url' => ':attribute একটি সঠিক URL হতে হবে।',

    'password' => [
        'letters' => ':attribute-এ কমপক্ষে একটি অক্ষর থাকতে হবে।',
        'mixed' => ':attribute-এ কমপক্ষে একটি বড় ও একটি ছোট হাতের অক্ষর থাকতে হবে।',
        'numbers' => ':attribute-এ কমপক্ষে একটি সংখ্যা থাকতে হবে।',
        'symbols' => ':attribute-এ কমপক্ষে একটি চিহ্ন থাকতে হবে।',
        'uncompromised' => 'এই :attribute ডেটা ফাঁসের তালিকায় পাওয়া গেছে। অনুগ্রহ করে অন্য পাসওয়ার্ড বেছে নিন।',
    ],

    'custom' => [],

    'attributes' => [
        'name' => 'নাম',
        'email' => 'ইমেইল',
        'password' => 'পাসওয়ার্ড',
        'password_confirmation' => 'পাসওয়ার্ড নিশ্চিতকরণ',
        'code' => 'কোড',
    ],
];

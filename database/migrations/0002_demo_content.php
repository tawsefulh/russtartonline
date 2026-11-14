<?php
/**
 * Demo content: a few student accounts with articles and marketplace advertisements,
 * so the site does not look empty on day one.
 *
 * - Runs only while 'demo_data' => true in the config (otherwise it waits).
 * - Every demo account's email ends in @russtart.demo. To remove all demo content later:
 *     DELETE FROM users WHERE email LIKE '%@russtart.demo';
 *   (their articles and advertisements are deleted with them)
 * - Demo accounts get random passwords, so nobody can log in to them.
 * - Phone numbers are not real (+7 000 ...); Telegram names start with demo_.
 */
return function (array $config): bool {
    if (empty($config['demo_data'])) {
        return false;
    }
    if (q("SELECT 1 FROM users WHERE email LIKE '%@russtart.demo' LIMIT 1")->fetch()) {
        return true;                                    // already there
    }

    $city = fn(string $slug) => (int) q('SELECT id FROM cities WHERE slug = ?', [$slug])->fetchColumn();
    $cat = fn(string $type, string $slug) => (int) q('SELECT id FROM categories WHERE type = ? AND slug = ?', [$type, $slug])->fetchColumn();

    // ---------- people ----------
    $people = [
        'tanvir' => ['Tanvir Ahmed', 'novosibirsk', 'Novosibirsk State University (NSU)'],
        'nusrat' => ['Nusrat Jahan', 'novosibirsk', 'Novosibirsk State Technical University (NSTU)'],
        'arjunm' => ['Arjun Mehta', 'kazan', 'Kazan Federal University (KFU)'],
        'mariamh' => ['Mariam Hassan', 'moscow', 'RUDN University'],
        'chidio' => ['Chidi Okafor', 'saint-petersburg', 'Saint Petersburg State University (SPbU)'],
        'linhng' => ['Linh Nguyen', 'tomsk', 'Tomsk State University (TSU)'],
        'olgas' => ['Ольга Смирнова', 'novosibirsk', null],
    ];
    $ids = [];
    $joined = 70;
    foreach ($people as $username => [$name, $citySlug, $university]) {
        q('INSERT INTO users (name, username, email, password_hash, city_id, university, created_at)
           VALUES (?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)',
            [$name, $username, $username . '@russtart.demo', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
             $city($citySlug), $university, $joined]);
        $ids[$username] = (int) db()->lastInsertId();
        $joined -= 4;
    }

    // ---------- articles: [author, city, category, days ago, views, title, body] ----------
    $articles = [
        ['tanvir', 'novosibirsk', 'arrival-registration', 2, 214, 'My first week in Novosibirsk: the paperwork in the right order', <<<'TXT'
I landed at Tolmachevo in late August with two suitcases and a folder of documents. Looking back, the first week was mostly paperwork, and doing it in the right order saved me a lot of queues.

## Day 1: keep the migration card

At passport control you get a small migration card. Do not lose it. The dormitory office asked for it together with my passport and visa to start my registration.

## Days 1 to 3: registration at the dormitory

Your university registers you at your address, but only after you hand in the papers. I gave mine to the international office on the second day and picked up the registration slip a few days later. Ask them how many working days you have; for me the deadline was short.

## Week 1: copies, photos and the medical exam

Make several copies of your passport, visa and migration card, and keep passport-size photos with you. The international office then told us when to go for the medical check and fingerprinting. Go as a group with other new students: it is faster and less stressful.

## What I would tell my younger self

Put every document in one plastic folder, write down the office hours of the international office, and ask questions early. Everyone there has helped hundreds of students like us.
TXT],
        ['nusrat', 'novosibirsk', 'winter', 5, 389, 'Surviving minus 35: what I actually wear to class in Novosibirsk', <<<'TXT'
Before coming from Dhaka I had never seen snow. My first January in Novosibirsk had a week of minus 35, and I still went to every class. Here is what worked.

## Layers, not one huge coat

A thin thermal layer, a warm sweater and a good down jacket keep you warmer than one heavy coat. Classrooms are hot, so you need to take layers off inside.

## Boots matter more than anything

Buy winter boots with a thick sole and room for wool socks. My first cheap pair let the cold through in ten minutes. Students sell good boots on the marketplace every spring, so it is worth checking.

## Protect your face and hands

A scarf over your nose, a hat that covers your ears, and mittens rather than gloves. Your phone battery also dies quickly in the cold, so keep it in an inside pocket.

## The good part

The sky is clear and blue on the coldest days, the snow squeaks under your feet, and the city is beautiful. Buy the boots early and you will enjoy it.
TXT],
        ['tanvir', 'novosibirsk', 'housing-dormitory', 9, 276, 'Akademgorodok dormitory: what the room really includes', <<<'TXT'
Many of us get a place in the university dormitories in Akademgorodok. The photos online never show the details, so this is what my room actually had.

## In the room

Two beds, two desks, two chairs, a wardrobe and shelves. Bedding was given at the start of the year. There was no kettle, lamp, extension cord or curtains, so bring or buy those in the first days.

## Shared spaces

A kitchen on each floor with electric stoves, and showers and washing machines on the ground floor. Label your pans and food; things go missing in shared kitchens everywhere in the world.

## Rules worth knowing

Guests have to leave by a set time and the entrance uses a pass. Keep your pass with you, because the guard will not always remember your face in the first weeks.

## What to buy first

An extension cord, a desk lamp, a kettle, slippers, and a small pot. Check the marketplace here first: students who are leaving sell all of these cheaply.
TXT],
        ['nusrat', 'novosibirsk', 'sim-internet', 13, 198, 'Getting a Russian SIM card on your first day', <<<'TXT'
Without a local number you cannot receive codes from banks, delivery apps or even some university services, so a SIM card was the first thing I sorted out.

## What you need

Your passport with the visa, and sometimes the migration card. The shop registers the number in your name, which takes about fifteen minutes.

## Which operator

MTS, Beeline, MegaFon and T2 all work well in Novosibirsk. Ask students in your dormitory which one has the best signal inside the building; ours had one operator that barely worked in the basement kitchen.

## Plans

Student-friendly plans with plenty of mobile internet are cheap compared to many countries. Choose one with enough data, because you will use maps and translation apps all the time at the beginning.

## Tip

Write your new number down on paper and send it to your family before your home SIM runs out of roaming.
TXT],
        ['tanvir', 'novosibirsk', 'transport', 18, 154, 'Getting from Akademgorodok to the city centre', <<<'TXT'
Akademgorodok feels like a small town in the forest, but sooner or later you need the city centre for shopping, documents or just a change of scene.

## Buses and minibuses

Several buses and minibuses go from Akademgorodok to the centre and to the metro. The ride takes around an hour depending on traffic. Use a maps app with public transport to see the routes and times.

## Paying

You can pay with a bank card on most buses. A transport card is useful if you travel often.

## The metro

Novosibirsk has a small metro with two lines. It is the fastest way to cross the city once you reach it, and the train crossing the Ob river on the metro bridge is a nice view.

## Taxi

For late evenings or heavy shopping, a taxi app is not expensive if you share it with friends.
TXT],
        ['nusrat', 'novosibirsk', 'food-groceries', 24, 241, 'Finding halal food and spices in Novosibirsk', <<<'TXT'
Food was my biggest worry before arriving. In the end it was easier than I thought.

## Supermarkets

Big supermarket chains have rice, lentils, chicken, vegetables and some spices. Look for the halal shelf; larger stores often have halal chicken and sausages.

## Markets

The central market has fresh meat, fruit and dried fruit. Sellers there will often tell you which meat is halal if you ask.

## Spices from home

Students from South and Central Asia run small shops and sell home-cooked food. Ask in your community chat, and check the marketplace on this site: I found someone selling biryani on Fridays.

## Cooking with friends

We cook together on weekends in the dormitory kitchen. It is cheaper, and it is the best way to fight homesickness in the first winter.
TXT],
        ['arjunm', 'kazan', 'university-life', 6, 167, 'Studying in Kazan: how the first semester really works', <<<'TXT'
Kazan is a great city to study in: it is lively, affordable, and very used to international students. Here is how my first semester at KFU went.

## The preparatory year

Many of us start with a year of Russian language before the main programme. It is intense, with classes every day, but by the end you can follow lectures.

## Attendance and tests

Attendance is taken seriously. Missing classes without a reason can cause problems with your exams, so tell your group leader if you are ill.

## Group chats

Every group has a chat where timetables and room changes appear. Ask to be added on the first day.

## Exams

Sessions are in winter and summer. Many exams are oral: you take a ticket with questions, prepare for a few minutes and then answer the teacher. Practise speaking your answers out loud.
TXT],
        ['mariamh', 'moscow', 'money-banking', 4, 302, 'Opening a bank card in Moscow as an international student', <<<'TXT'
International cards from home often do not work in Russia, so a local bank card is essential for daily life.

## Where

Many banks open accounts for students. Some universities even have a bank branch on campus, which is the easiest option because they are used to foreign passports.

## What to bring

Passport with visa, migration card and registration, and your Russian phone number. Some banks also ask for a student card or a letter from the university.

## Getting money from home

Ask your bank which transfer services work for your country, and compare the exchange rates and fees before sending large amounts. Rules change, so ask students from your country what works for them right now.

## Tip

Set up the bank's app on the same day. Payments by phone or card work almost everywhere in Moscow, even in small shops.
TXT],
        ['mariamh', 'moscow', 'transport', 11, 188, 'The Troika card and the Moscow metro without stress', <<<'TXT'
The Moscow metro looked huge and confusing on my first day. After a week I loved it.

## Troika

Buy a Troika card at any metro station and top it up at the machines or in an app. It works on the metro, buses and trams. Students can also apply for a reduced fare card through their university.

## Finding your way

Every line has a number and a colour. Signs are in Russian and English in most central stations, and announcements on many lines are in both languages too.

## Rush hour

Between eight and ten in the morning trains are very full. If your classes allow it, travel a little earlier.

## Stations worth seeing

Take a free afternoon just to ride around the circle line and get out at the old stations. Some of them look like museums.
TXT],
        ['chidio', 'saint-petersburg', 'russian-language', 8, 233, 'From zero Russian to ordering coffee in two months', <<<'TXT'
I arrived in Saint Petersburg knowing only "spasibo". Two months later I could order food, ask for directions and joke with the guard at the dormitory. This is what helped.

## Learn the alphabet in the first week

The Cyrillic alphabet takes a few days. After that you can read signs, menus and metro stations, and suddenly the city stops feeling foreign.

## Speak every day, badly

Shop assistants and older people are patient if you try. Say what you can, point at the rest, and listen to how they answer.

## Use your preparatory classes

The language courses for foreigners are very structured. Do the homework every day, and ask your teacher for the words you need in real life.

## Friends who do not speak English

The fastest progress came when I joined a football group where nobody spoke English. Mistakes stop being embarrassing very quickly.
TXT],
        ['linhng', 'tomsk', 'health-insurance', 15, 141, 'Health insurance and seeing a doctor in Tomsk', <<<'TXT'
Nobody plans to get sick, but in my first autumn in Tomsk I caught a bad flu and had to find out how the system works.

## Your insurance policy

International students need a medical insurance policy. Keep a copy on your phone and know which clinic it is attached to. Your international office can explain what it covers.

## Going to the clinic

Bring your passport and policy. I went to the clinic named in my policy; the doctor spoke a little English, and a translation app did the rest.

## Pharmacies

Pharmacies are everywhere and many medicines are sold without a prescription. Pharmacists will usually help if you show them your symptoms in a translation app.

## Emergency

The emergency number is 112, and it works from any phone. Save it, and save the address of your dormitory in Russian so you can tell it to an operator.
TXT],
        ['chidio', 'saint-petersburg', 'part-time-work', 21, 178, 'Part-time work as a student: what I learned the hard way', <<<'TXT'
In my second year I wanted to earn some money on the side. My advice: understand the rules before you take any job.

## Ask the international office first

Whether and where you may work depends on your status and programme, and the rules change from time to time. The international office knows the current rules. Ask them before you agree to anything.

## Jobs students often do

Tutoring English or your native language, translation, helping at university events, and some campus jobs. These fit around classes and often count as good experience.

## Watch out for

Anyone who asks you to work without a contract, keeps your passport, or pays only "later". Never give your original documents to an employer.

## Was it worth it

Tutoring was. I improved my Russian while teaching English, and met people outside the university.
TXT],
        ['linhng', 'tomsk', 'culture-daily-life', 27, 126, 'Small things that surprised me in Tomsk', <<<'TXT'
Tomsk is a student city with wooden houses, cosy cafes and long winters. A few small things surprised me in my first months.

## Taking off your shoes

At home and in many dormitories people change into slippers at the door. Bring a pair when you visit friends.

## Flowers come in odd numbers

When you give flowers, give an odd number. Even numbers are for funerals.

## People seem serious at first

Strangers rarely smile on the street, but once you know someone they are warm and generous. My neighbour brought me soup when I was ill.

## Name days, holidays and tea

There is always a reason for tea and something sweet. Say yes when you are invited: it is the best way to make friends.
TXT],
    ];
    foreach ($articles as [$who, $citySlug, $catSlug, $days, $views, $title, $body]) {
        q('INSERT INTO articles (user_id, city_id, category_id, title, slug, excerpt, body, views, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY - INTERVAL ? HOUR)',
            [$ids[$who], $city($citySlug), $cat('article', $catSlug), $title, unique_slug('articles', $title),
             excerpt($body), $body, $views, $days, ($views % 9) + 1]);
    }

    // ---------- advertisements: [seller, city, category, days ago, title, price, address, phone, whatsapp, telegram, description] ----------
    $ads = [
        ['olgas', 'novosibirsk', 'home-food', 1, 'Homemade Siberian pelmeni, frozen, 1 kg', 650, 'Morskoy prospekt 20, Akademgorodok', '+70000000101', null, '@demo_olga_pelmeni',
            "Handmade pelmeni with beef and pork, or with chicken. Frozen, 1 kg pack, about 50 pieces.\n\nI can bring them to the dormitories in Akademgorodok in the evening."],
        ['olgas', 'novosibirsk', 'rooms-flatmates', 3, 'Room for a female student near the NSU metro bus', 18000, 'Ulitsa Ilyicha 6, Akademgorodok', '+70000000102', '+70000000102', null,
            "A bright room in a two-room flat, 10 minutes' walk from the university. Furnished: bed, desk, wardrobe. Internet included.\n\nLooking for a quiet, tidy student. Price per month, utilities included."],
        ['tanvir', 'novosibirsk', 'winter-clothing', 2, 'Down winter jacket, size L, worn one winter', 4500, 'Pirogova 2, dormitory 9', null, '+70000000103', '@demo_tanvir',
            "Very warm down jacket, rated for Siberian winter. Size L, dark blue, hood with fur trim. No damage.\n\nI am going home after graduation, so everything must go."],
        ['tanvir', 'novosibirsk', 'books-study-materials', 6, 'Russian textbooks for foreigners, full set', 1200, 'Pirogova 2, dormitory 9', null, '+70000000103', '@demo_tanvir',
            "The textbooks we used in the preparatory year, with a few pencil notes. Saves you a trip to the bookshop in your first week."],
        ['tanvir', 'novosibirsk', 'kitchen-appliances', 10, 'Rice cooker, 1.8 litres', 1500, 'Pirogova 2, dormitory 9', null, '+70000000103', null,
            "Works perfectly, cooks rice for four people. Comes with the measuring cup and spoon."],
        ['nusrat', 'novosibirsk', 'home-food', 4, 'Bengali home food every Friday: biryani and curry', null, 'Pirogova 16, dormitory 6', null, '+70000000104', '@demo_nusrat',
            "Chicken biryani, beef curry and dal, cooked at home every Friday. Halal. Order by Thursday evening.\n\nPrice depends on the dish and the portion: message me for this week's menu."],
        ['nusrat', 'novosibirsk', 'electronics-gadgets', 8, 'Desk lamp and 5-socket extension cord', 700, 'Pirogova 16, dormitory 6', null, '+70000000104', '@demo_nusrat',
            "LED desk lamp with three brightness levels, and a 3-metre extension cord with five sockets. The two things every dormitory room is missing."],
        ['nusrat', 'novosibirsk', 'winter-clothing', 12, 'Women\'s winter boots, size 38', 2500, 'Pirogova 16, dormitory 6', null, '+70000000104', null,
            "Warm boots with natural fur inside and a thick sole that does not slip. Worn one winter, still in very good condition."],
        ['arjunm', 'kazan', 'bicycles-transport', 5, 'City bicycle with lock, good for the KFU campus', 6500, 'Ulitsa Kremlyovskaya, near KFU main building', '+70000000105', null, '@demo_arjun',
            "21 gears, new tyres this spring, comes with a strong lock and lights. Perfect for getting around the centre in warm months."],
        ['arjunm', 'kazan', 'services', 14, 'Airport pickup and first-day help in Kazan', null, 'Kazan, any address', '+70000000105', '+70000000105', '@demo_arjun',
            "Arriving in Kazan for the first time? I can meet you at the airport, help with a SIM card and take you to your dormitory.\n\nI speak English, Hindi and Russian. Message me with your flight details."],
        ['mariamh', 'moscow', 'kitchen-appliances', 3, 'Microwave oven, almost new', 3000, 'Ulitsa Miklukho-Maklaya 21, building 2', null, '+70000000106', '@demo_mariam',
            "20-litre microwave, bought six months ago. Moving to a furnished flat, so I do not need it any more."],
        ['mariamh', 'moscow', 'furniture-home', 9, 'Desk and chair, easy to carry', 3500, 'Ulitsa Miklukho-Maklaya 21, building 2', null, '+70000000106', '@demo_mariam',
            "White desk 100 x 60 cm with an office chair. Light enough for two people to carry to a nearby dormitory."],
        ['chidio', 'saint-petersburg', 'electronics-gadgets', 7, 'Laptop 15.6", 16 GB RAM, good for studies', 32000, 'Vasilyevsky Island, near SPbU', '+70000000107', null, '@demo_chidi',
            "Three years old, new battery, fast SSD. Runs everything you need for coursework and video calls home. Charger included."],
        ['chidio', 'saint-petersburg', 'services', 16, 'English conversation practice for Russian learners', 800, 'Online or near Vasileostrovskaya metro', null, null, '@demo_chidi',
            "Friendly conversation practice in English, one hour. Also happy to swap: an hour of English for an hour of Russian practice with you."],
        ['linhng', 'tomsk', 'rooms-flatmates', 11, 'Looking for a flatmate near Tomsk State University', 12000, 'Prospekt Lenina, 10 minutes to TSU', null, '+70000000108', '@demo_linh',
            "I am looking for a calm flatmate for a two-room flat. Your room is furnished, the kitchen is shared. Price per month for your room."],
    ];
    foreach ($ads as [$who, $citySlug, $catSlug, $days, $title, $price, $address, $phone, $whatsapp, $telegram, $description]) {
        q('INSERT INTO ads (user_id, city_id, category_id, title, slug, description, price, address, phone, whatsapp, telegram, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY - INTERVAL ? HOUR)',
            [$ids[$who], $city($citySlug), $cat('ad', $catSlug), $title, unique_slug('ads', $title), $description,
             $price, $address, $phone, $whatsapp, $telegram, $days, strlen($title) % 7]);
    }

    return true;
};

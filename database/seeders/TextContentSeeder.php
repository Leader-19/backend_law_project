<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\TextContent;
use App\Models\User;
use Illuminate\Database\Seeder;

class TextContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->first();

        if (! $admin) {
            $this->command?->warn('No admin user found. Skipping TextContentSeeder.');

            return;
        }

        $categories = Category::orderBy('title')->get();

        if ($categories->isEmpty()) {
            $this->command?->warn('No categories found. Skipping TextContentSeeder.');

            return;
        }

        $sampleTexts = [
            [
                'title' => 'Introduction to Law',
                'body' => 'Law is a system of rules created and enforced through social or governmental institutions to regulate behavior. It has been defined both as "the Science of Justice" and "the Art of Justice". Law is a system that regulates and ensures that individuals or a community adhere to the will of the state.',
            ],
            [
                'title' => 'Constitutional Law Basics',
                'body' => 'Constitutional law deals with the interpretation and implementation of the Constitution. It concerns itself with the relationship between the government and the governed, as well as the fundamental rights of citizens. A constitution is a set of fundamental principles or established precedents according to which a state or other organization is acknowledged to be governed.',
            ],
            [
                'title' => 'Civil Law Overview',
                'body' => 'Civil law is the legal system used in most countries around the world today. In civil law, sources of law include codes, statutes, and regulations. Civil law is sometimes called codified law. The main feature of civil law systems is that the laws are codified into collections.',
            ],
            [
                'title' => 'Criminal Law Principles',
                'body' => 'Criminal law is the body of law that relates to crime. It proscribes conduct perceived as threatening, harmful, or otherwise endangering to the property, health, safety, and moral welfare of people. Most criminal law is established by statute, which is to say that the laws are enacted by a legislature.',
            ],
            [
                'title' => 'Contract Law Fundamentals',
                'body' => 'A contract is a legally binding agreement between two or more parties. It creates obligations that are enforceable by law. The essential elements of a contract include offer, acceptance, consideration, capacity, and intention to create legal relations.',
            ],
            [
                'title' => 'Property Law Essentials',
                'body' => 'Property law governs the various forms of ownership in real property and personal property. It encompasses the law of deeds, mortgages, easements, land use restrictions, and landlord-tenant relationships.',
            ],
            [
                'title' => 'International Law Concepts',
                'body' => 'International law is the set of rules, norms, and standards generally accepted in relations between nations. It establishes normative guidelines and a common conceptual framework for states across a broad range of domains, including war, diplomacy, trade, and human rights.',
            ],
            [
                'title' => 'Human Rights Law',
                'body' => 'Human rights are moral principles or norms that describe certain standards of human behavior and are regularly protected as natural and legal rights in municipal and international law. They are commonly understood as inalienable fundamental rights to which a person is inherently entitled simply because they are a human being.',
            ],
            [
                'title' => 'Environmental Law',
                'body' => 'Environmental law is a collective term describing the network of treaties, statutes, regulations, common and customary laws addressing the effects of human activity on the natural environment. The main areas of environmental law include air quality, water quality, waste management, and biodiversity conservation.',
            ],
            [
                'title' => 'Labor Law Principles',
                'body' => 'Labor law mediates the relationship between workers, employers, trade unions, and governments. It deals with the rights and duties of workers, unions, and employers. Labor law deals with the tripartite relationship between employee, employer, and the union.',
            ],
            [
                'title' => 'Tax Law Basics',
                'body' => 'Tax law is the body of laws governing taxation. Tax laws are the rules under which a public authority, typically a government, levies a tax. Tax law is part of public law and should not be confused with private law, which deals with disputes between individuals.',
            ],
            [
                'title' => 'Commercial Law Overview',
                'body' => 'Commercial law, also known as business law, is the body of law that applies to the rights, relations, and conduct of persons and businesses engaged in commerce, merchandising, trade, and sales. It includes areas such as contract law, property law, and intellectual property.',
            ],
            [
                'title' => 'Family Law Fundamentals',
                'body' => 'Family law is an area of legal practice that focuses on issues involving family relationships such as marriage, adoption, divorce, and child custody. Attorneys practicing family law can represent clients in family court proceedings or in related negotiations.',
            ],
            [
                'title' => 'Administrative Law',
                'body' => 'Administrative law is the body of law that governs the activities of administrative agencies of government. Government agency action can include rulemaking, adjudication, or the enforcement of a specific regulatory agenda. Administrative law is considered a branch of public law.',
            ],
            [
                'title' => 'Tort Law Essentials',
                'body' => 'A tort is a civil wrong that causes a claimant to suffer loss or harm, resulting in legal liability for the person who commits the tortious act. Tort law is part of the common law and covers areas such as negligence, defamation, trespass, and nuisance.',
            ],
        ];

        $count = 0;

        for ($i = 0; $i < 50; $i++) {
            $sample = $sampleTexts[$i % count($sampleTexts)];
            $category = $categories->random();

            TextContent::create([
                'title' => $sample['title'].($i >= count($sampleTexts) ? ' (Part '.(intdiv($i, count($sampleTexts)) + 1).')' : ''),
                'body' => $sample['body'],
                'category_id' => $category->id,
                'user_id' => $admin->id,
            ]);

            $count++;
        }

        $this->command?->info("Created {$count} test text contents.");
    }
}

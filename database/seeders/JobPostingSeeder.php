<?php

namespace Database\Seeders;

use App\Models\JobPosting;
use Illuminate\Database\Seeder;

class JobPostingSeeder extends Seeder
{
    public function run(): void
    {
        JobPosting::create([
            'title' => 'Social Media & Client Coordinator',
            'slug' => 'social-media-client-coordinator',
            'description' => '<p>As an employee of our company, you will collaborate with each department to create and deploy disruptive products. Come work at a growing company that offers great benefits with opportunities to moving forward and learn alongside accomplished leaders. We\'re seeking an enthusiastic, energetic and outstanding performer.</p>
<p>This position is both creative and rigorous by nature you need to think outside the box. We expect the candidate to be proactive and have a "get it done" spirit. To be successful, you will have solid creative skills.</p>
<h3>Responsibilities</h3>
<ul>
<li>Creating engaging content for our social media platforms to promote books, events and store</li>
<li>Designing perfect graphics, pricelists, short form videos using Canva, Capcut or any other applications</li>
<li>Running paid campaigns per month targeting local readers</li>
<li>Planning & scheduling original posts across social platforms (mixing reels, carousels, stories and threads)</li>
<li>Monitoring comments, DMs, and mentions and responding to them</li>
</ul>',
            'requirements' => '<h3>Must Have</h3>
<ul>
<li>Certificate/ Diploma in Graphic Design or Advertising</li>
<li>Passion for graphic designing, and creative products</li>
<li>Perfect written English</li>
<li>Highly creative and autonomous</li>
<li>Experience in Digital marketing</li>
</ul>
<h3>Nice to Have</h3>
<ul>
<li>Experience in writing online content</li>
</ul>',
            'department' => 'Marketing',
            'location' => 'Harare, Zimbabwe',
            'employment_type' => 'full_time',
            'experience_level' => 'entry',
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
}

<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\Article;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Class ArticleFixtures
 *
 * ArticleFixtures loads rich sample data for articles to demonstrate editor capabilities
 *
 * @package App\DataFixtures
 */
class ArticleFixtures extends Fixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager The EntityManager
     *
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();

        $titles = [
            'The Future of Web Development in 2026',
            'Mastering CSS Grid and Flexbox',
            'Why PHP is Alway Alive',
            'Symfony 7.4: The Game Changer',
            'Docker Optimization Secrets',
            'Understanding Database Indexing',
            'The Art of Clean Code',
            'Frontend Performance Tips',
            'Microservices vs Monolith',
            'Secure Coding Practices'
        ];

        foreach ($titles as $index => $title) {
            $article = new Article();
            $status = $index > 7 ? 'draft' : 'published';
            if ($index === 5) {
                $status = 'hidden';
            }

            $article->setTitle($title);
            $article->setSlug($slugger->slug($title)->lower());

            // generate rich content with colors and structure
            $article->setContent($this->generateRichContent($title, $index));
            $article->setStatus($status);
            $article->setPublishTime(new DateTime('-' . ($index * 2) . ' days'));

            if ($index % 3 === 0) {
                $article->setEditedTime(new DateTime('-' . $index . ' hours'));
            }

            // save article
            $manager->persist($article);
        }

        // flush articles to database
        $manager->flush();
    }

    /**
     * Generates rich HTML content with styling
     *
     * @param string $title The article title
     * @param int $seed The random seed
     *
     * @return string The generated HTML content
     */
    private function generateRichContent(string $title, int $seed): string
    {
        $colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#a855f7', '#ec4899'];
        $mainColor = $colors[$seed % count($colors)];
        $secondaryColor = $colors[($seed + 2) % count($colors)];

        return '
            <p style="font-size: 1.2rem; color: #a0aec0;">
                <em>An in-depth look at <strong>' . $title . '</strong> and how it impacts modern software engineering.</em>
            </p>

            <h2 style="color: ' . $mainColor . ';">1. Introduction</h2>
            <p>
                Lorem ipsum dolor sit amet, <span style="color: ' . $secondaryColor . '; font-weight: bold;">consectetur adipiscing elit</span>. 
                Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.
            </p>

            <blockquote style="border-left: 4px solid ' . $mainColor . '; padding-left: 1rem; margin: 1.5rem 0; color: #cbd5e0; font-style: italic;">
                "Technology is best when it brings people together. Or when it compiles without errors."
            </blockquote>

            <h2 style="color: ' . $mainColor . ';">2. Key Concepts</h2>
            <p>Here are the critical components you need to understand:</p>

            <ul>
                <li><strong style="color: ' . $secondaryColor . ';">Scalability:</strong> Handling growth gracefully.</li>
                <li><strong style="color: ' . $secondaryColor . ';">Maintainability:</strong> Writing code for humans.</li>
                <li><strong style="color: ' . $secondaryColor . ';">Performance:</strong> Speed matters.</li>
            </ul>

            <h3 style="color: ' . $mainColor . '; opacity: 0.9;">2.1 Code Example</h3>
            <p>Consider the following snippet representing our logic:</p>
            
            <pre style="background-color: #1a202c; padding: 1rem; border-radius: 0.5rem; color: #e2e8f0; border: 1px solid #4a5568; overflow-x: auto;">
<code>// This is a sample code block
function calculateSuccess(effort, consistency) {
    return effort * consistency;
}

const result = calculateSuccess(100, 100);
console.log("Success Level: " + result);</code></pre>

            <p style="margin-top: 1rem;">
                This demonstrates how <span style="background-color: ' . $secondaryColor . '; color: white; padding: 0.1rem 0.3rem; border-radius: 0.2rem;">simple logic</span> can drive complex systems.
            </p>

            <h2 style="color: ' . $mainColor . ';">3. Detailed Analysis</h2>
            <p>
                Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
                Excepteur sint occaecat cupidatat non proident.
            </p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 1rem 0; border: 1px solid #4a5568;">
                <tr style="background-color: #2d3748;">
                    <th style="padding: 0.5rem; border: 1px solid #4a5568; color: ' . $mainColor . ';">Metric</th>
                    <th style="padding: 0.5rem; border: 1px solid #4a5568; color: ' . $mainColor . ';">Value</th>
                    <th style="padding: 0.5rem; border: 1px solid #4a5568; color: ' . $mainColor . ';">Status</th>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568;">Velocity</td>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568;">High</td>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568; color: #48bb78;">Optimal</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568;">Complexity</td>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568;">Medium</td>
                    <td style="padding: 0.5rem; border: 1px solid #4a5568; color: #ecc94b;">Manageable</td>
                </tr>
            </table>

            <h2 style="color: ' . $mainColor . ';">4. Conclusion</h2>
            <p>
                In conclusion, mastering these skills requires patience and practice. 
                <span style="text-decoration: underline; text-decoration-color: ' . $secondaryColor . ';">Keep learning and stay curious.</span>
            </p>
        ';
    }
}

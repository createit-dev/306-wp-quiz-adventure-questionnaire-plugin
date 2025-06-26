# WP QuizAdventure

WP QuizAdventure is a free WordPress plugin that allows you to create a custom questionnaires with yes/no questions grouped into steps. It is designed with a strong emphasis on security and performance, and is released under the MIT license.

![WP_QuizAdventure_plugin.jpg](img%2FWP_QuizAdventure_plugin.jpg)

## Features

- **Custom post types** for quizzes and questions.
- **Custom taxonomy** for steps.
- Results list custom wp table with columns for: ID, Hash, Total Score %, Raw Score, Max Score Possible, Recommendations, Email Share Count, and Date.
- Ability to **add and delete steps** in the questionnaire.
- **Thank you messages** (customizable messages that are displayed to users after they have completed a questionnaire).
- Allows for **recommendations** to be added to each question.
- Option to **change the order of steps** (drag and drop).
- Creates a table upon plugin activation to **store results**.
- Allows for **email sharing of quiz results** with a custom button (using ajax submit).
- **Yes/No questions** (radio buttons).
- **Pretty permalinks** for results page, example: https://www.example.com/results/12345.
- Displays results in admin panel using a **WP_List_Table for easy score viewing**.
- Includes **built-in mailtrap functionality** for testing emails.
- Calculates the **score and recommendations automatically** after form submit.
- "Next" and "Previous" buttons to navigate between steps.
- **Analytics for count sharing** (our plugin keeps track of the number of times results have been shared via email).
- **Simple validation** (checking if all questions in a particular step have been answered before proceeding to the next step).
- **Ajax from handler** (saves the user's results, calculate final score and generates results hash).
- Using **nonce for form security** (checking whether the request came from a trusted source).

## Installation and Setup

To activate the plugin, first, log in to your WordPress admin dashboard.

1. Navigate to the 'Plugins' menu on the left sidebar.
2. Click on 'Add New' and then use the search box to find the 'WP QuizAdventure' plugin or use Upload Plugin button.
3. Click on the 'Activate' button to activate the plugin.
4. Once the plugin is activated, you will notice two new menu items in your WordPress admin dashboard sidebar: "Questions" and "Quizzes".

![WP_QuizAdventure_admin_panel.jpg](img%2FWP_QuizAdventure_admin_panel.jpg)

![WP_QuizAdventure_adding_quiz.jpg](img%2FWP_QuizAdventure_adding_quiz.jpg)

To configure the plugin and set up the first quiz with steps and questions, follow these steps:

1. To create a new step, click on the 'Steps' submenu item. New step will be added after “Add New Category” button click.
2. Now, navigate to the 'Questions'/ Add New menu item to create questions. Enter the question's title in the 'Title' field. On the right sidebar, you will find the 'Steps' section. Choose the appropriate step for the question from the list. You can fill custom fields: Score Value and Recommendation Text. Click on the 'Publish' button to save and create the question. Repeat this process for all the questions you want to add to your quiz.
3. To create a new quiz, go to the 'Quizzes' menu item. Click on the 'Add New' button at the top of the page. Enter a title for your quiz in the 'Title' field. Assign the steps you created to the quiz by selecting them from the 'Steps' section. You can change the order of steps by dragging and dropping them into the desired sequence.  Click on the 'Publish' button to save and create the quiz.
4. To display the quiz on a page or post, use the provided shortcode. The shortcode format is  [cq_questionnaire id=""], which can be placed in the content editor of any page or post where you want the quiz to appear.
5. Go to the page/post where you want the results to appear, and paste the shortcode [questionnaire_results] into the content editor. It is preferable to use the page permalink: /results/.
6. Click on 'Update' or 'Publish' to save the changes to the page/post.

Now visit the frontend of the page or post where you added the shortcode, and you will see the quiz with steps and questions configured according to your setup.

## Using the Plugin

The shortcode `[cq_questionnaire id=""]` is used to display the quiz on the page. To navigate between steps, you can use the navigation buttons that are automatically added to the questionnaire.

- If you are on a step other than the first step, a "Previous" button will be displayed.
- If you are on a step other than the last step, a "Next" button will be displayed.
- If you are on the last step, a "Submit" button will be displayed.

When the user clicks the submit button, the JavaScript code first checks if all questions have been answered. If not, an alert message is displayed asking the user to answer all questions before submitting.

If all questions have been answered, the form data is serialized and sent via AJAX to the server. The server-side code handles the form submission by parsing the serialized form data and calculating the score and recommendations. The total percentage score is calculated by dividing the total score by the maximum possible score and multiplying by 100.

On success, the user is redirected to the results page. The results are retrieved from the database using the hash value provided in the URL query string. The hash is used to identify the specific result to display. Once the result is retrieved, the total score, recommendations, and thank you message are displayed on the page.

Users can also enter their email address to receive or share their results via email.

## Example quiz

To help you visualize the potential of our plugin, we've prepared a sample quiz, complete with five questions, steps, scoring values, personalized recommendation text, and a thank you messages:

1. **Question:** Do you exercise regularly?
    - Yes/No answers
    - Score: 2 for Yes, 0 for No
    - Recommendation text: Exercising regularly contributes significantly to a healthy lifestyle.
    - Step: Physical Activity

2. **Question:** Do you eat a balanced diet?
    - Yes/No answers
    - Score: 1 for Yes, 0 for No
    - Recommendation text: A balanced diet is essential for maintaining overall health and well-being.
    - Step: Nutrition

3. **Question:** Do you drink at least 8 glasses of water daily?
    - Yes/No answers
    - Score: 3 for Yes, 0 for No
    - Recommendation text: Drinking enough water is vital to prevent dehydration and support overall health.
    - Step: Nutrition

4. **Question:** Do you get at least 7-8 hours of sleep every night?
    - Yes/No answers
    - Score: 2 for Yes, 0 for No
    - Recommendation text: Proper sleep is crucial for the body to recover and function optimally.
    - Step: Rest & Recovery

5. **Question:** Do you manage stress effectively?
    - Yes/No answers
    - Score: 1 for Yes, 0 for No
    - Recommendation text: Managing stress is important to maintain mental and emotional health.
    - Step: Rest & Recovery

**Thank you messages:**

- Score 0%-40%: Making some improvements to your daily habits can lead to better overall health. Consider focusing on exercise, nutrition, sleep, stress management, and hydration for a healthier lifestyle.
- Score 41%-75%: You are taking some steps towards a healthier lifestyle, but there is still room for improvement. Prioritize areas in which you scored lower to make the biggest impact on your overall health.
- Score 76%-100%: Congratulations! You are leading a healthy lifestyle, making conscious decisions to care for your mind and body. Keep up this fantastic work, and continue being a role model for others.
- 

Read more on our site: https://www.createit.com/blog/mastering-wordpress-quiz-plugins/

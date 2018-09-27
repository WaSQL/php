# Contributing to WaSQL

Enjoying [WaSQL](http://www.wasql.com) and want to get
involved? Great! There are plenty of ways you can help out.

Please take a moment to review this document in order to make the contribution
process easy and effective for everyone involved.

Following these guidelines helps to communicate that you respect the time of
the developers managing and developing this open source project. In return,
they should reciprocate that respect in addressing your issue or assessing
patches and features.


## Using the issue tracker

The [issue tracker](https://github.com/WaSQL/v2/issues) is
the preferred channel for changes: spelling mistakes, wording changes, new
content and generally [submitting pull requests](#pull-requests), but please
respect the following restrictions:

* Please **do not** use the issue tracker for personal support requests (use
  [Stack Overflow](http://stackoverflow.com/questions/tagged/php) or IRC).

* Please **do not** derail or troll issues. Keep the discussion on topic and
  respect the opinions of others.


<a name="pull-requests"></a>
## Pull Requests

Pull requests are a great way to add new content to WaSQL, as well as updating any browser issues or other style changes. 
Pretty much any sort of change is accepted if seen as constructive.

Adhering to the following this process is the best way to get your work included in the project:

0. Fork WaSQL. If you don't have an SSH key yet, create one and add it to your profile.
  
  ```ssh-keygen```

1. If you have not downloade WaSQL yet, get the code:

   ```git clone git@github.com:yourusername/v2.git```

2. Change directory into v2 and set the URL.

  ```git remote set-url origin git@github.com:yourusername/v2.git```

3. Add an remote to track waSQL (it doesn't have to be named "upstream").

  ```git remote add upstream git@github.com:WaSQL/v2.git```

4. To get the latest code:

  ```git fetch upstream```
  ```git merge upstream upstream/master```

5. Add your changes to the stage. You may have to enter your email and name.

6. Commit your changes.

  ```git commit -m "Your message here."```

7. Push your changes to your repository.

  ```git push -u origin master```

8. On the repository you are adding to, create a pull request and wait for your changes to be approved.

## Contribution Agreement and Usage

By submitting a pull request to this repository, you agree to follow our [code of conduct](https://github.com/WaSQL/v2/blob/master/CODE_OF_CONDUCT.md)

All content is completely free now, and always will be.

## Contributor Style Guide

1. Use American English spelling (*primary English repo only*)
2. Use tabs to indent text; do not use spaces
3. Wrap all text to 120 characters
4. Code samples should adhere to PSR-1 or higher
5. Use [GitHub Flavored Markdown](http://github.github.com/github-flavored-markdown/) for all content
6. Use language agnostic urls when referring to external websites such as the [php.net](http://php.net/urlhowto.php) manual

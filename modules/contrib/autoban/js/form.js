/**
 * @file
 * Autoban rules and settings form behaviors.
 */

document.addEventListener("DOMContentLoaded", function (event) {
  'use strict';

  // Function to handle click event
  function handleClick(event, targetId) {
    var text = event.target.textContent;
    var input = document.getElementById(targetId);
    if (input) {
      input.value = text;
    }
  }

  // Attach click event listener for type description spans
  var typeDescriptionSpans = document.querySelectorAll('#edit-type--description span');
  if (typeDescriptionSpans) {
    typeDescriptionSpans.forEach(function (span) {
      span.addEventListener('click', function (event) {
        handleClick(event, 'edit-type');
      });
    });
  }

  // Attach click event listener for autoban window default description spans
  var windowDescriptionSpans = document.querySelectorAll('#edit-autoban-window-default--description span');
  if (windowDescriptionSpans) {
    windowDescriptionSpans.forEach(function (span) {
      span.addEventListener('click', function (event) {
        handleClick(event, 'edit-autoban-window-default');
      });
    });
  }
});

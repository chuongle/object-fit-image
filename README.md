# Object Fit - Drupal 8 Module

## Usage:

- Create an image field. 
- Set the display of the image as object-fit. 
- Find the parent container that wrap around the image and assign the selector to Parent Selector input.
- Apply image style if need.
- Apply `data-image="object-fit"` attribute to the parent container in Twig Template
- Render image inside of the container

## Example:
```
  <div class="container background" data-image="object-fit">
    <h3>This is the title</h3>
    <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
    {{ content.field_image }}
  </div>
```
## TODO
- Explain more about issue
- Post more screenshots